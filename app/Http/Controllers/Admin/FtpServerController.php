<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FtpServer;
use App\Services\AccServerConfigService;
use App\Services\FtpService;
use Illuminate\Http\Request;

class FtpServerController extends Controller
{
    public function index()
    {
        $servers = FtpServer::orderBy('name')->get();

        return view('admin.servers.index', compact('servers'));
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
        ]);

        FtpServer::create($request->only(
            'name', 'server_number', 'host', 'port', 'username', 'password', 'path', 'cfg_path',
            'server_type', 'reset_start_hour', 'reset_interval_minutes'
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
            'username'                => 'required|string|max:100',
            'path'                    => 'required|string|max:255',
            'cfg_path'                => 'nullable|string|max:255',
            'server_type'             => 'required|in:rolling,scheduled',
            'reset_start_hour'        => 'required_if:server_type,rolling|integer|min:0|max:23',
            'reset_interval_minutes'  => 'required_if:server_type,rolling|integer|min:30|max:1440',
            'event_defaults'          => 'nullable|string',
            'settings_defaults'       => 'nullable|string',
            'eventrules_defaults'     => 'nullable|string',
            'assistrules_defaults'    => 'nullable|string',
        ];

        if ($request->filled('password')) {
            $rules['password'] = 'string|max:255';
        }

        $request->validate($rules);

        $data = $request->only(
            'name', 'server_number', 'host', 'port', 'username', 'path', 'cfg_path',
            'server_type', 'reset_start_hour', 'reset_interval_minutes'
        );
        $data['active'] = $request->boolean('active');

        foreach (['event_defaults', 'settings_defaults', 'eventrules_defaults', 'assistrules_defaults'] as $field) {
            $raw = trim($request->input($field, ''));
            if ($raw === '') {
                $data[$field] = null;
            } else {
                $decoded = json_decode($raw, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return back()->withInput()->withErrors([$field => 'Invalid JSON: ' . json_last_error_msg()]);
                }
                $data[$field] = $decoded;
            }
        }

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        $ftpServer->update($data);

        return redirect()->route('admin.servers.index')->with('success', 'Server updated.');
    }

    public function destroy(FtpServer $ftpServer)
    {
        $ftpServer->delete();

        return redirect()->route('admin.servers.index')->with('success', 'Server deleted.');
    }

    public function pushDefaults(FtpServer $ftpServer, FtpService $ftp, AccServerConfigService $config)
    {
        $files = [
            'settings.json'    => json_encode($config->settings(new \App\Models\Race(), $ftpServer), JSON_PRETTY_PRINT),
            'eventrules.json'  => json_encode($config->eventRules($ftpServer), JSON_PRETTY_PRINT),
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