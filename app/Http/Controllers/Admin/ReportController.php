<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::with(['user', 'race', 'reviewer'])
            ->orderByRaw("FIELD(status, 'pending', 'investigating', 'resolved', 'dismissed')")
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.reports.index', compact('reports'));
    }

    public function show(Report $report)
    {
        $report->load(['user', 'race', 'reviewer']);
        return view('admin.reports.show', compact('report'));
    }

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
}