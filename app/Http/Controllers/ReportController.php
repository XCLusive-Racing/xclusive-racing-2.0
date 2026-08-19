<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Message;
use App\Models\Race;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::where('user_id', auth()->id())
            ->with('race')
            ->orderBy('created_at', 'desc')
            ->get();

        $races = Race::select(['id','title','track','scheduled_at'])
            ->where('status', 'finished')
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $driverNames = Driver::whereNotNull('gamertag')
            ->orderBy('gamertag')
            ->pluck('gamertag')
            ->unique()
            ->values();

        $myGamertag = $this->myGamertag(auth()->user());

        return view('reports.index', compact('reports', 'races', 'driverNames', 'myGamertag'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'race_id'               => 'nullable|exists:races,id',
            'reported_driver_name'  => 'required|string|max:100',
            'reporter_driver_name'  => 'nullable|string|max:100',
            'session_type'          => 'required|in:R,Q,P',
            'lap_number'            => 'nullable|integer|min:1|max:999',
            'incident_corner'       => 'nullable|string|max:50',
            'description'           => 'required|string|min:20|max:2000',
            'video_url'             => 'nullable|url|max:500',
            'clip_good_driver_url'  => 'nullable|url|max:500',
            'clip_bad_driver_url'   => 'nullable|url|max:500',
            'clip_heli_url'         => 'nullable|url|max:500',
        ]);

        $data['user_id'] = auth()->id();
        $data['reporter_driver_name'] = $data['reporter_driver_name'] ?: $this->myGamertag(auth()->user());

        $report = Report::create($data);

        Message::create([
            'user_id'      => auth()->id(),
            'title'        => 'Report submitted',
            'body'         => "Your incident report has been received and is pending review by the stewards.\n\nReported driver: {$report->reported_driver_name}\n\nYou will receive a message when a verdict has been reached.",
            'type'         => 'report_confirmation',
            'related_id'   => $report->id,
            'related_type' => Report::class,
        ]);

        return redirect()->route('reports.index')
            ->with('success', 'Your report has been submitted and is pending review.');
    }

    private function myGamertag(\App\Models\User $user): string
    {
        $driver = Driver::where('xuid_psid', $user->platform_id)
            ->orWhere('xuid_psid', 'T_' . strtolower($user->name))
            ->orWhere('gamertag', $user->name)
            ->first();

        return $driver->gamertag ?? $user->displayName();
    }
}