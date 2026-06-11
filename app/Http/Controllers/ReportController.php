<?php

namespace App\Http\Controllers;

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

        return view('reports.index', compact('reports', 'races'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'race_id'               => 'nullable|exists:races,id',
            'reported_driver_name'  => 'required|string|max:100',
            'lap_number'            => 'nullable|integer|min:1|max:999',
            'incident_corner'       => 'nullable|string|max:50',
            'description'           => 'required|string|min:20|max:2000',
            'video_url'             => 'nullable|url|max:500',
        ]);

        $data['user_id'] = auth()->id();

        Report::create($data);

        return redirect()->route('reports.index')
            ->with('success', 'Your report has been submitted and is pending review.');
    }
}