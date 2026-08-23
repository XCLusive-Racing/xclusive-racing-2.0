<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Report;
use App\Models\ReportVerdict;
use App\Models\User;
use App\Services\PenaltyCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::with(['user', 'race', 'reviewer', 'steward1', 'steward2'])
            ->withCount('verdicts')
            ->orderByRaw("FIELD(status, 'pending', 'investigating', 'resolved', 'dismissed')")
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.reports.index', compact('reports'));
    }

    public function show(Report $report)
    {
        $report->load(['user', 'race', 'reviewer', 'steward1', 'steward2', 'processedBy', 'verdicts.steward']);

        $penaltyCodes = PenaltyCalculator::codes();
        $reportedUser = $report->reportedUser();
        $ratingFields = $report->ratingFields();
        $reportedRating = $ratingFields && $reportedUser ? (float) ($reportedUser->{$ratingFields['elo']} ?? 0) : 0.0;

        return view('admin.reports.show', compact('report', 'penaltyCodes', 'reportedUser', 'reportedRating'));
    }

    /** Legacy quick status update — superseded by the verdict workflow below but kept for direct status corrections. */
    public function updateStatus(Request $request, Report $report)
    {
        $request->validate([
            'status'      => 'required|in:pending,investigating,resolved,dismissed',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $previousStatus = $report->status;

        $report->update([
            'status'      => $request->status,
            'admin_notes' => $request->admin_notes,
            'reviewed_by' => auth()->id(),
        ]);

        if (in_array($request->status, ['resolved', 'dismissed']) && $previousStatus !== $request->status) {
            $label  = $request->status === 'resolved' ? 'Resolved' : 'Dismissed';
            $notes  = $request->admin_notes ? "\n\nSteward verdict:\n{$request->admin_notes}" : '';

            Message::create([
                'user_id'      => $report->user_id,
                'title'        => "Report {$label}: {$report->reported_driver_name}",
                'body'         => "Your incident report against {$report->reported_driver_name} has been {$report->status}.{$notes}",
                'type'         => 'report_resolved',
                'related_id'   => $report->id,
                'related_type' => Report::class,
            ]);
        }

        return redirect()->route('admin.reports.show', $report)->with('success', 'Report status updated.');
    }

    /** Claims the free steward_1/steward_2 slot for the current user and moves the report to investigating. */
    public function startInvestigating(Report $report)
    {
        $user = auth()->user();

        if (in_array($report->status, ['resolved', 'dismissed'])) {
            return back()->with('error', 'This report has already been closed.');
        }

        if ($report->isStewardAssigned($user)) {
            if ($report->status === 'pending') {
                $report->update(['status' => 'investigating']);
            }
            return back()->with('success', 'You are already assigned to this report.');
        }

        if ($report->steward_1_id && $report->steward_2_id) {
            return back()->with('error', 'Both steward slots are filled — you can still submit an additional verdict below.');
        }

        $slot = $report->steward_1_id ? 2 : 1;

        $report->update([
            'status'                => 'investigating',
            "steward_{$slot}_id"    => $user->id,
        ]);

        return back()->with('success', "You are now assigned as Steward {$slot}.");
    }

    public function submitVerdict(Request $request, Report $report)
    {
        if (in_array($report->status, ['resolved', 'dismissed'])) {
            return back()->with('error', 'This report has already been closed.');
        }

        $data = $request->validate([
            'penalty'    => ['required', Rule::in(array_keys(PenaltyCalculator::codes()))],
            'multiplier' => 'required|numeric|in:1,2,3',
            'red_flag'   => 'nullable|boolean',
            'notes'      => 'nullable|string|max:2000',
        ]);

        $user = auth()->user();
        $redFlag = $request->boolean('red_flag');

        DB::transaction(function () use ($report, $user, $data, $redFlag) {
            ReportVerdict::updateOrCreate(
                ['report_id' => $report->id, 'steward_id' => $user->id],
                [
                    'penalty'    => $data['penalty'],
                    'multiplier' => $data['multiplier'],
                    'red_flag'   => $redFlag,
                    'notes'      => $data['notes'] ?? null,
                ]
            );

            $updates = [];

            if ($report->status === 'pending') {
                $updates['status'] = 'investigating';
            }

            $slot = $report->slotFor($user);

            // First verdict from an unassigned steward claims a free slot automatically.
            if (! $slot) {
                if (! $report->steward_1_id) {
                    $slot = 1;
                    $updates['steward_1_id'] = $user->id;
                } elseif (! $report->steward_2_id) {
                    $slot = 2;
                    $updates['steward_2_id'] = $user->id;
                }
            }

            if ($slot) {
                $updates["steward_{$slot}_verdict"]    = $data['penalty'];
                $updates["steward_{$slot}_penalty"]    = $data['penalty'];
                $updates["steward_{$slot}_multiplier"] = $data['multiplier'];
                $updates["steward_{$slot}_notes"]      = $data['notes'] ?? null;
                $updates["steward_{$slot}_red_flag"]   = $redFlag;
            }

            // Any new/changed verdict before processing invalidates a prior "ready" state.
            if (! $report->processed_at) {
                $updates['ready_to_process'] = false;
                $updates['final_penalty']    = null;
                $updates['final_multiplier'] = null;
            }

            $report->update($updates);
        });

        return back()->with('success', 'Verdict submitted.');
    }

    public function markReady(Report $report)
    {
        $report->load('verdicts');

        if (in_array($report->status, ['resolved', 'dismissed'])) {
            return back()->with('error', 'This report has already been closed.');
        }

        $pair = $report->matchingVerdictPair();

        if (! $pair) {
            return back()->with('error', 'No two stewards agree on the same penalty and multiplier yet.');
        }

        if ($report->anyRedFlagActive()) {
            return back()->with('error', 'A red flag is active — it must be cleared before this can be marked ready.');
        }

        [$a] = $pair;

        $reportedUser   = $report->reportedUser();
        $ratingFields   = $report->ratingFields();
        $reportedRating = $ratingFields && $reportedUser ? (float) ($reportedUser->{$ratingFields['elo']} ?? 0) : 0.0;

        $calc = PenaltyCalculator::calculate($a->penalty, $a->multiplier, $report->session_type, $reportedRating);

        $report->update([
            'ready_to_process'     => true,
            'final_penalty'        => $a->penalty,
            'final_multiplier'     => $a->multiplier,
            'xcl_rating_deduction' => $calc['rating_deduction'],
            'xcl_rating_return'    => $calc['rating_return'],
            'sr_deduction'         => $calc['sr_deduction'],
        ]);

        return back()->with('success', 'Marked ready to process.');
    }

    public function dismiss(Request $request, Report $report)
    {
        if (in_array($report->status, ['resolved', 'dismissed'])) {
            return back()->with('error', 'This report has already been closed.');
        }

        $data = $request->validate([
            'dismissal_reason' => 'required|string|max:2000',
        ]);

        $report->update([
            'status'            => 'dismissed',
            'dismissal_reason'  => $data['dismissal_reason'],
            'reviewed_by'       => auth()->id(),
            'ready_to_process'  => false,
        ]);

        Message::create([
            'user_id'      => $report->user_id,
            'title'        => "Report dismissed: {$report->reported_driver_name}",
            'body'         => "Your incident report against {$report->reported_driver_name} has been dismissed.\n\nReason:\n{$data['dismissal_reason']}",
            'type'         => 'report_resolved',
            'related_id'   => $report->id,
            'related_type' => Report::class,
        ]);

        return redirect()->route('admin.reports.show', $report)->with('success', 'Report dismissed.');
    }

    public function process(Report $report)
    {
        if (! $report->ready_to_process || $report->processed_at) {
            return back()->with('error', 'This report is not ready to process.');
        }

        DB::transaction(function () use ($report) {
            $noPenalty    = PenaltyCalculator::isNoPenalty($report->final_penalty);
            $reportedUser = $report->reportedUser();
            $ratingFields = $report->ratingFields();

            if (! $noPenalty && $reportedUser && $ratingFields) {
                $newElo = max(0, (float) $reportedUser->{$ratingFields['elo']} - (float) $report->xcl_rating_deduction);
                $newSr  = max(0, (float) $reportedUser->{$ratingFields['sr']} - (float) $report->sr_deduction);

                $reportedUser->update([
                    $ratingFields['elo'] => (int) round($newElo),
                    $ratingFields['sr']  => round($newSr, 2),
                ]);

                if ($report->session_type === 'R' && $report->user) {
                    $reporter = $report->user;
                    $reporter->update([
                        $ratingFields['elo'] => (int) round((float) $reporter->{$ratingFields['elo']} + (float) $report->xcl_rating_return),
                    ]);
                }
            }

            $banReview = ! $noPenalty && $report->final_penalty === 'CAIC';

            $report->update([
                'status'             => $noPenalty ? 'dismissed' : 'resolved',
                'processed_at'       => now(),
                'processed_by'       => auth()->id(),
                'ban_review_flagged' => $banReview,
            ]);

            if ($banReview && $reportedUser) {
                $reportedUser->update([
                    'ban_review_flagged_at' => now(),
                    'ban_review_reason'     => "CAIC penalty processed on report #{$report->id} against {$report->reported_driver_name} — intentional collision, needs manual ban review.",
                    'ban_review_report_id'  => $report->id,
                ]);

                $admins = User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['owner', 'admin']))->get();

                foreach ($admins as $admin) {
                    Message::create([
                        'user_id'      => $admin->id,
                        'title'        => 'Ban review required',
                        'body'         => "Report #{$report->id} against {$report->reported_driver_name} was processed with a CAIC penalty and needs manual ban review.",
                        'type'         => 'report_resolved',
                        'related_id'   => $report->id,
                        'related_type' => Report::class,
                    ]);
                }
            }

            if ($reportedUser) {
                Message::create([
                    'user_id'      => $reportedUser->id,
                    'title'        => $noPenalty ? 'Report against you dismissed' : "Penalty applied: {$report->final_penalty}",
                    'body'         => $noPenalty
                        ? 'An incident report against you has been reviewed and dismissed.'
                        : "An incident report against you has been processed.\n\nPenalty: {$report->final_penalty}\nXCL Rating deduction: -{$report->xcl_rating_deduction}\nSR deduction: -{$report->sr_deduction}",
                    'type'         => 'report_resolved',
                    'related_id'   => $report->id,
                    'related_type' => Report::class,
                ]);
            }

            if ($report->user) {
                Message::create([
                    'user_id'      => $report->user_id,
                    'title'        => 'Report processed: ' . $report->reported_driver_name,
                    'body'         => "Your incident report against {$report->reported_driver_name} has been processed.\n\nFinal penalty: {$report->final_penalty}"
                        . ($report->session_type === 'R' && ! $noPenalty ? "\nRating returned to you: +{$report->xcl_rating_return}" : ''),
                    'type'         => 'report_resolved',
                    'related_id'   => $report->id,
                    'related_type' => Report::class,
                ]);
            }
        });

        return redirect()->route('admin.reports.show', $report)->with('success', 'Penalty processed.');
    }
}
