<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FtpServer;
use App\Services\FtpService;
use Illuminate\Http\Request;

class FtpBrowserController extends Controller
{
    public function index(FtpServer $ftpServer, Request $request)
    {
        $path    = $this->sanitizePath($request->input('path', '/'));
        $ftp     = new FtpService();
        $entries = [];
        $error   = null;

        if ($ftp->connect($ftpServer)) {
            $entries = $ftp->listDirectory($path);
            $ftp->disconnect();
        } else {
            $error = 'Could not connect to ' . $ftpServer->host . '.';
        }

        return view('admin.servers.browse', [
            'server'  => $ftpServer,
            'path'    => $path,
            'entries' => $entries,
            'error'   => $error,
            'crumbs'  => $this->breadcrumbs($path),
        ]);
    }

    public function view(FtpServer $ftpServer, Request $request)
    {
        $path = $this->sanitizePath($request->input('path', ''));

        if ($path === '' || $path === '/') {
            abort(400);
        }

        $ftp = new FtpService();

        if (!$ftp->connect($ftpServer)) {
            return response()->json(['error' => 'Could not connect to ' . $ftpServer->host . '.'], 502);
        }

        $content = $ftp->getFileContent($path);
        $ftp->disconnect();

        if ($content === false) {
            return response()->json(['error' => 'Could not read file.'], 502);
        }

        $decoded = json_decode($content);
        $pretty  = $decoded !== null
            ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : $content;

        return response($pretty, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function download(FtpServer $ftpServer, Request $request)
    {
        $path = $this->sanitizePath($request->input('path', ''));

        if ($path === '' || $path === '/') {
            abort(400);
        }

        $ftp = new FtpService();

        if (!$ftp->connect($ftpServer)) {
            return back()->with('error', 'Could not connect to ' . $ftpServer->host . '.');
        }

        $content = $ftp->getFileContent($path);
        $ftp->disconnect();

        if ($content === false) {
            return back()->with('error', 'Could not download file.');
        }

        return response($content, 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . basename($path) . '"',
        ]);
    }

    public function upload(FtpServer $ftpServer, Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'file' => 'required|file|max:51200',
        ]);

        $dir  = $this->sanitizePath($request->input('path'));
        $file = $request->file('file');
        $dest = rtrim($dir, '/') . '/' . $file->getClientOriginalName();

        $ftp = new FtpService();

        if (!$ftp->connect($ftpServer)) {
            return back()->with('error', 'Could not connect to ' . $ftpServer->host . '.');
        }

        $ok = $ftp->uploadFile($dest, file_get_contents($file->getRealPath()));
        $ftp->disconnect();

        return redirect()->route('admin.servers.browse', ['ftpServer' => $ftpServer->id, 'path' => $dir])
            ->with($ok ? 'success' : 'error', $ok
                ? basename($dest) . ' uploaded successfully.'
                : 'Upload failed.');
    }

    public function mkdir(FtpServer $ftpServer, Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'name' => ['required', 'string', 'max:100', 'regex:/^[^\\/\\\\:*?"<>|]+$/'],
        ]);

        $parent = $this->sanitizePath($request->input('path'));
        $newDir = rtrim($parent, '/') . '/' . $request->input('name');

        $ftp = new FtpService();

        if (!$ftp->connect($ftpServer)) {
            return back()->with('error', 'Could not connect to ' . $ftpServer->host . '.');
        }

        $ok = $ftp->makeDirectory($newDir);
        $ftp->disconnect();

        return redirect()->route('admin.servers.browse', ['ftpServer' => $ftpServer->id, 'path' => $parent])
            ->with($ok ? 'success' : 'error', $ok
                ? 'Directory "' . $request->input('name') . '" created.'
                : 'Failed to create directory.');
    }

    public function delete(FtpServer $ftpServer, Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'type' => 'required|in:file,dir',
        ]);

        $path   = $this->sanitizePath($request->input('path'));
        $parent = $this->parentPath($path);
        $ftp    = new FtpService();

        if (!$ftp->connect($ftpServer)) {
            return back()->with('error', 'Could not connect to ' . $ftpServer->host . '.');
        }

        $ok = $request->input('type') === 'dir'
            ? $ftp->deleteDirectory($path)
            : $ftp->deleteFile($path);

        $ftp->disconnect();

        return redirect()->route('admin.servers.browse', ['ftpServer' => $ftpServer->id, 'path' => $parent])
            ->with($ok ? 'success' : 'error', $ok
                ? '"' . basename($path) . '" deleted.'
                : 'Delete failed.');
    }

    public function rename(FtpServer $ftpServer, Request $request)
    {
        $request->validate([
            'path'    => 'required|string',
            'newname' => ['required', 'string', 'max:255', 'regex:/^[^\\/\\\\:*?"<>|]+$/'],
        ]);

        $from   = $this->sanitizePath($request->input('path'));
        $parent = $this->parentPath($from);
        $to     = rtrim($parent, '/') . '/' . $request->input('newname');
        $ftp    = new FtpService();

        if (!$ftp->connect($ftpServer)) {
            return back()->with('error', 'Could not connect to ' . $ftpServer->host . '.');
        }

        $ok = $ftp->renameFile($from, $to);
        $ftp->disconnect();

        return redirect()->route('admin.servers.browse', ['ftpServer' => $ftpServer->id, 'path' => $parent])
            ->with($ok ? 'success' : 'error', $ok
                ? 'Renamed to "' . $request->input('newname') . '".'
                : 'Rename failed.');
    }

    private function sanitizePath(string $path): string
    {
        $parts = array_filter(explode('/', $path), fn($p) => $p !== '' && $p !== '.');
        $stack = [];
        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($stack);
            } else {
                $stack[] = $part;
            }
        }
        return '/' . implode('/', $stack);
    }

    private function parentPath(string $path): string
    {
        $dir = dirname($path);
        return ($dir === '.' || $dir === '') ? '/' : $dir;
    }

    private function breadcrumbs(string $path): array
    {
        $crumbs = [['name' => 'Root', 'path' => '/']];
        $parts  = array_filter(explode('/', $path), fn($p) => $p !== '');
        $built  = '';
        foreach ($parts as $part) {
            $built    .= '/' . $part;
            $crumbs[] = ['name' => $part, 'path' => $built];
        }
        return $crumbs;
    }
}