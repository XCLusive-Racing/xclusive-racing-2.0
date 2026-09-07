<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PushPracticeServerConfigJob;
use App\Models\PracticeServerSession;

class PracticeServerSessionController extends Controller
{
    public function index()
    {
        $sessions = PracticeServerSession::with(['race:id,title,scheduled_at', 'practiceServer.ftpServer:id,name'])
            ->orderByDesc('window_start')
            ->limit(100)
            ->get();

        $upcoming = $sessions->whereIn('status', [
            PracticeServerSession::STATUS_SCHEDULED,
            PracticeServerSession::STATUS_PUSHING,
            PracticeServerSession::STATUS_LIVE,
        ])->sortBy('window_start')->values();

        $recent = $sessions->whereIn('status', [
            PracticeServerSession::STATUS_COMPLETED,
            PracticeServerSession::STATUS_FAILED,
            PracticeServerSession::STATUS_CANCELLED,
            PracticeServerSession::STATUS_TOO_LATE,
        ])->values();

        return view('admin.practice-servers.index', compact('upcoming', 'recent'));
    }

    // Runs the exact same job immediately, bypassing the schedule — this is the "a
    // failed silent push is the main way this feature disappoints people" escape hatch.
    public function push(PracticeServerSession $practiceServerSession)
    {
        $practiceServerSession->update(['status' => PracticeServerSession::STATUS_PUSHING]);

        PushPracticeServerConfigJob::dispatchSync($practiceServerSession);

        return back()->with('success', 'Practice server push triggered for "' . ($practiceServerSession->race->title ?? 'event') . '".');
    }
}
