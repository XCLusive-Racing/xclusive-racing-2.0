<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FtpServer;
use App\Services\AuditLogger;
use App\Services\Contracts\ServerConfigGenerator;
use App\Services\FtpService;
use Illuminate\Http\Request;

class FtpServerController extends Controller
{
    public function index()
    {
        $servers = FtpServer::orderBy('name')->get();

        return view('admin.servers.index', compact('servers'));
    }

    public function schedule()
    {
        $servers = FtpServer::with(['races' => function ($q) {
            $q->where('status', '!=', 'finished')
              ->where('scheduled_at', '>=', now())
              ->orderBy('scheduled_at');
        }])->orderBy('name')->get();

        return view('admin.servers.schedule', compact('servers'));
    }

    public function create()
    {
        return view('admin.servers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                    => 'required|string|max:150',
            'server_number'           => 'nullable|integer|min:1|max:9',
            'host'                    => 'required|string|max:255',
            'port'                    => 'required|integer|min:1|max:65535',
            'username'                => 'required|string|max:100',
            'password'                => 'required|string|max:255',
            'path'                    => 'required|string|max:255',
            'cfg_path'                => 'nullable|string|max:255',
            'server_type'             => 'required|in:rolling,scheduled',
            'reset_start_hour'        => 'required_if:server_type,rolling|integer|min:0|max:23',
            'reset_interval_minutes'  => 'required_if:server_type,rolling|integer|min:30|max:1440',
            'game'                    => 'required|in:acc,lmu',
            'platform'                => 'required|in:pc,console,cross',
        ]);

        $server = FtpServer::create($request->only(
            'name', 'server_number', 'host', 'port', 'username', 'password', 'path', 'cfg_path',
            'server_type', 'reset_start_hour', 'reset_interval_minutes', 'game', 'platform'
        ));

        AuditLogger::record($request->user(), $server, 'ftp_server.created', $request->only(
            'name', 'server_number', 'host', 'port', 'path', 'cfg_path', 'server_type'
        ));

        return redirect()->route('admin.servers.index')->with('success', 'Server added.');
    }

    public function edit(FtpServer $ftpServer)
    {
        return view('admin.servers.edit', ['server' => $ftpServer]);
    }

    public function update(Request $request, FtpServer $ftpServer)
    {
        $rules = [
            'name'                    => 'required|string|max:150',
            'server_number'           => 'nullable|integer|min:1|max:9',
            'host'                    => 'required|string|max:255',
            'port'                    => 'required|integer|min:1|max:65535',
            'path'                    => 'required|string|max:255',
            'cfg_path'                => 'nullable|string|max:255',
            'server_type'             => 'required|in:rolling,scheduled',
            'reset_start_hour'        => 'required_if:server_type,rolling|integer|min:0|max:23',
            'reset_interval_minutes'  => 'required_if:server_type,rolling|integer|min:30|max:1440',
            // Nullable, not required — an existing server already has both from the
            // Phase 3 backfill; a request that omits them (an older/partial submit)
            // leaves the current value untouched, same idiom as username/password below.
            'game'                    => 'nullable|in:acc,lmu',
            'platform'                => 'nullable|in:pc,console,cross',
            'event_defaults'          => 'nullable|string',
            'settings_defaults'       => 'nullable|string',
            'eventrules_defaults'     => 'nullable|string',
            'assistrules_defaults'    => 'nullable|string',
        ];

        // Credential fields render empty in the form and only overwrite the stored,
        // encrypted value when a new one is actually submitted.
        if ($request->filled('username')) {
            $rules['username'] = 'string|max:255';
        }
        if ($request->filled('password')) {
            $rules['password'] = 'string|max:255';
        }

        $request->validate($rules);

        $data = $request->only(
            'name', 'server_number', 'host', 'port', 'path', 'cfg_path',
            'server_type', 'reset_start_hour', 'reset_interval_minutes', 'game', 'platform'
        );
        $data['active'] = $request->boolean('active');

        $configService = app(ServerConfigGenerator::class);
        $builtInDefaults = [
            'event_defaults'       => $configService->defaultEventConfig(),
            'settings_defaults'    => $configService->defaultSettings(),
            'eventrules_defaults'  => $configService->defaultEventRules(),
            'assistrules_defaults' => $configService->defaultAssistRules(),
        ];

        foreach ($builtInDefaults as $field => $builtIn) {
            $raw = trim($request->input($field, ''));
            if ($raw === '') {
                $data[$field] = null;
                continue;
            }
            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withInput()->withErrors([$field => 'Invalid JSON: ' . json_last_error_msg()]);
            }
            $data[$field] = ($decoded == $builtIn) ? null : $decoded;
        }

        if ($request->filled('username')) {
            $data['username'] = $request->input('username');
        }
        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        $ftpServer->update($data);

        AuditLogger::record($request->user(), $ftpServer, 'ftp_server.updated', $request->only(
            'name', 'server_number', 'host', 'port', 'path', 'cfg_path', 'server_type', 'active'
        ));

        return redirect()->route('admin.servers.index')->with('success', 'Server updated.');
    }

    public function destroy(Request $request, FtpServer $ftpServer)
    {
        AuditLogger::record($request->user(), $ftpServer, 'ftp_server.deleted', ['name' => $ftpServer->name, 'host' => $ftpServer->host]);
        $ftpServer->delete();

        return redirect()->route('admin.servers.index')->with('success', 'Server deleted.');
    }

    public function pushDefaults(FtpServer $ftpServer, FtpService $ftp, ServerConfigGenerator $config)
    {
        $files = [
            'settings.json'    => json_encode($config->settings(new \App\Models\Race(), $ftpServer), JSON_PRETTY_PRINT),
            'eventrules.json'  => json_encode($config->eventRules(null, $ftpServer), JSON_PRETTY_PRINT),
            'assistrules.json' => json_encode($config->assistRules($ftpServer), JSON_PRETTY_PRINT),
        ];

        foreach ($files as $filename => $content) {
            json_decode($content);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', "Invalid JSON in {$filename}: " . json_last_error_msg());
            }
        }

        if (!$ftp->connect($ftpServer)) {
            return back()->with('error', 'Could not connect to ' . $ftpServer->host . '.');
        }

        $cfgPath = rtrim($ftpServer->cfg_path, '/');
        $failed  = [];
        foreach ($files as $filename => $content) {
            if (!$ftp->uploadFile($cfgPath . '/' . $filename, $content)) {
                $failed[] = $filename;
            }
        }

        $ftp->disconnect();

        if ($failed) {
            return back()->with('error', 'Failed to upload: ' . implode(', ', $failed));
        }

        return back()->with('success', 'Config defaults pushed to ' . $ftpServer->name . ' — settings.json, eventrules.json, assistrules.json uploaded.');
    }

    public function test(FtpServer $ftpServer, FtpService $ftp)
    {
        if (!extension_loaded('curl')) {
            return response()->json(['success' => false, 'message' => 'PHP cURL extension not loaded.']);
        }

        $ok = $ftp->connect($ftpServer);
        $ftp->disconnect();

        return response()->json(['success' => $ok]);
    }
}