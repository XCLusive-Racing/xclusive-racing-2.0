<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FtpServer;
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
            'name'     => 'required|string|max:150',
            'host'     => 'required|string|max:255',
            'port'     => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:255',
            'path'     => 'required|string|max:255',
            'cfg_path' => 'nullable|string|max:255',
        ]);

        FtpServer::create($request->only('name', 'host', 'port', 'username', 'password', 'path', 'cfg_path'));

        return redirect()->route('admin.servers.index')->with('success', 'Server added.');
    }

    public function edit(FtpServer $ftpServer)
    {
        return view('admin.servers.edit', ['server' => $ftpServer]);
    }

    public function update(Request $request, FtpServer $ftpServer)
    {
        $rules = [
            'name'     => 'required|string|max:150',
            'host'     => 'required|string|max:255',
            'port'     => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:100',
            'path'     => 'required|string|max:255',
            'cfg_path' => 'nullable|string|max:255',
        ];

        if ($request->filled('password')) {
            $rules['password'] = 'string|max:255';
        }

        $request->validate($rules);

        $data           = $request->only('name', 'host', 'port', 'username', 'path', 'cfg_path');
        $data['active'] = $request->boolean('active');

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