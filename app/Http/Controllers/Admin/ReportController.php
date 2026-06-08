<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $report->update([
            'status'      => $request->status,
            'admin_notes' => $request->admin_notes,
            'reviewed_by' => auth()->id(),
        ]);

        return redirect()->route('admin.reports.show', $report)->with('success', 'Report status updated.');
    }
}