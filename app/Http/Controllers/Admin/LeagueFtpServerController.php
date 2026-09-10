<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FtpServer;
use App\Models\League;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

// The master, cross-league view of every FTP server a league brings or is assigned —
// deliberately separate from admin.servers.* (Configuration), which lists XCL's own
// core servers. Owner/Admin only: a league manager sees and manages only their own
// league's servers from the League edit page, never this aggregate list.
class LeagueFtpServerController extends Controller
{
    public function index()
    {
        $servers = FtpServer::withoutTenantScope()
            ->where('league_id', '!=', League::system()->id)
            ->with('league')
            ->orderBy('league_id')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($s) => $s->league?->name ?? 'Unknown league');

        $leagues = League::withoutTenantScope()->orderBy('name')->get();

        return view('admin.league-servers.index', compact('servers', 'leagues'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'league_id'              => 'required|exists:leagues,id',
            'name'                   => 'required|string|max:150',
            'host'                   => 'required|string|max:255',
            'port'                   => 'required|integer|min:1|max:65535',
            'username'               => 'required|string|max:100',
            'password'               => 'required|string|max:255',
            'path'                   => 'required|string|max:255',
            'cfg_path'               => 'nullable|string|max:255',
            'server_type'            => 'required|in:rolling,scheduled',
            'reset_start_hour'       => 'required_if:server_type,rolling|integer|min:0|max:23',
            'reset_interval_minutes' => 'required_if:server_type,rolling|integer|min:30|max:1440',
            'game'                   => 'required|in:acc,lmu',
            'platform'               => 'required|in:pc,console,cross',
        ]);

        $data['active'] = true;

        $server = FtpServer::create($data);

        AuditLogger::record($request->user(), $server, 'ftp_server.created', $request->only('name', 'host', 'path', 'server_type', 'league_id'));

        return redirect()->route('admin.league-servers.index')->with('success', $server->name . ' added.');
    }
}
