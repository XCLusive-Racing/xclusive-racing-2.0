<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PushPracticeServerConfigJob;
use App\Models\PracticeServerSession;
use App\Services\PracticeServer\PracticeServerConfigService;

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

    // Read-only dry run of the exact files PushPracticeServerConfigJob would write —
    // built the same way (PracticeServerConfigService::buildFiles) but never touches FTP,
    // so admins can check e.g. entry counts or car assignments before a push goes out.
    public function preview(PracticeServerSession $practiceServerSession, PracticeServerConfigService $configService)
    {
        $session = $practiceServerSession->fresh([
            'race.ftpServer'           => fn ($q) => $q->withoutTenantScope(),
            'practiceServer.ftpServer' => fn ($q) => $q->withoutTenantScope(),
        ]);

        abort_if(!$session || !$session->race || !$session->practiceServer, 404);

        $race           = $session->race;
        $practiceServer = $session->practiceServer;
        $ftpServer      = $practiceServer->ftpServer;

        [$files, $entryListResult] = $configService->buildFiles($race, $session, $practiceServer);
        $gap = $configService->admissionGap($race, $practiceServer);

        $cfgPath = $ftpServer ? rtrim($ftpServer->cfg_path ?? '/cfg', '/') : null;

        return view('admin.practice-servers.preview', [
            'session'          => $session,
            'race'             => $race,
            'practiceServer'   => $practiceServer,
            'ftpServer'        => $ftpServer,
            'cfgPath'          => $cfgPath,
            'files'            => $files,
            'entryListResult'  => $entryListResult,
            'gap'              => $gap,
        ]);
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
