<?php

namespace App\Services;

use App\Models\FtpServer;

class FtpService
{
    private ?FtpServer $server = null;

    public function connect(FtpServer $server): bool
    {
        if (!extension_loaded('curl')) {
            return false;
        }

        $ch     = $this->makeCurl($server, rtrim($server->path, '/') . '/');
        $result = curl_exec($ch);
        $error  = curl_errno($ch);
        curl_close($ch);

        if ($error !== 0 || $result === false) {
            return false;
        }

        $this->server = $server;
        return true;
    }

    public function listFiles(string $path): array
    {
        if (!$this->server) {
            return ['json' => [], 'all' => []];
        }

        $raw = $this->tryList($path);

        if ($raw === null) {
            return ['json' => [], 'all' => []];
        }

        $all = array_values(array_filter(
            array_map('basename', $raw),
            fn($f) => $f !== '' && $f !== '.' && $f !== '..'
        ));

        $meta = $this->parseRawList($path);

        $json = [];
        foreach ($all as $filename) {
            $lower = strtolower($filename);

            if (!str_ends_with($lower, '.json')) continue;
            if (str_contains($lower, '_fp_') || str_ends_with($lower, '_fp.json')) continue;
            if (str_contains($lower, 'entrylist')) continue;

            $json[] = [
                'name'     => $filename,
                'size'     => $meta[$filename]['size'] ?? null,
                'modified' => $meta[$filename]['modified'] ?? null,
            ];
        }

        usort($json, fn($a, $b) => strcmp($b['name'], $a['name']));

        return ['json' => $json, 'all' => $all];
    }

    private function makeCurl(FtpServer $server, string $path): \CurlHandle
    {
        $path = '/' . ltrim($path, '/');
        $url  = "ftp://{$server->host}:{$server->port}{$path}";

        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_URL, $url);
        curl_setopt($ch, \CURLOPT_USERPWD, "{$server->username}:{$server->password}");
        curl_setopt($ch, defined('CURLOPT_FTP_USE_PASV') ? \CURLOPT_FTP_USE_PASV : 119, true);
        curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, \CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);

        return $ch;
    }

    private function tryList(string $path): ?array
    {
        $candidates = array_unique([
            rtrim($path, '/') . '/',
            '/' . ltrim(rtrim($path, '/'), '/') . '/',
            $path,
            '/' . ltrim($path, '/'),
        ]);

        foreach ($candidates as $p) {
            $ch     = $this->makeCurl($this->server, $p);
            $result = curl_exec($ch);
            $error  = curl_errno($ch);
            curl_close($ch);

            if ($error !== 0 || $result === false || trim($result) === '') {
                continue;
            }

            $files = $this->parseListingToFilenames($result);

            if (count($files) > 0) {
                return $files;
            }
        }

        return null;
    }

    private function parseListingToFilenames(string $listing): array
    {
        $files = [];
        foreach (explode("\n", trim($listing)) as $line) {
            $line = trim($line);
            if ($line === '' || $line === '.' || $line === '..') continue;

            if (preg_match('/\s(\S+)\s*$/', $line, $m)) {
                $files[] = $m[1];
            } else {
                $files[] = $line;
            }
        }
        return $files;
    }

    private function parseRawList(string $path): array
    {
        $candidates = [
            '/' . rtrim(ltrim($path, '/'), '/') . '/',
            $path,
        ];

        $listing = null;
        foreach ($candidates as $p) {
            $ch     = $this->makeCurl($this->server, $p);
            $result = curl_exec($ch);
            $error  = curl_errno($ch);
            curl_close($ch);

            if ($error === 0 && $result !== false && trim($result) !== '') {
                $listing = $result;
                break;
            }
        }

        if ($listing === null) {
            return [];
        }

        $meta = [];

        foreach (explode("\n", trim($listing)) as $line) {
            $line = trim($line);

            // Unix style: -rw-r--r-- 1 user group 45632 May 28 12:34 filename.json
            if (preg_match(
                '/^[d\-][rwx\-]{9}\S*\s+\d+\s+\S+\s+\S+\s+(\d+)\s+(\w+\s+\d+\s+[\d:]+)\s+(.+)$/',
                $line, $m
            )) {
                $meta[basename(trim($m[3]))] = [
                    'size'     => (int) $m[1],
                    'modified' => trim($m[2]),
                ];
                continue;
            }

            // Windows/IIS style: 05-29-26  02:34PM  45632 filename.json
            if (preg_match('/^(\d{2}-\d{2}-\d{2}\s+\d{2}:\d{2}[AP]M)\s+(\d+)\s+(.+)$/', $line, $m)) {
                $meta[basename(trim($m[3]))] = [
                    'size'     => (int) $m[2],
                    'modified' => trim($m[1]),
                ];
            }
        }

        return $meta;
    }

    public function getFileContent(string $fullPath): string|false
    {
        if (!$this->server) {
            return false;
        }

        $ch = $this->makeCurl($this->server, $fullPath);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $content = curl_exec($ch);
        $error   = curl_errno($ch);
        curl_close($ch);

        if ($error !== 0 || $content === false) {
            return false;
        }

        return $content;
    }

    public function uploadFile(string $remotePath, string $content): bool
    {
        if (!$this->server) {
            return false;
        }

        $path = '/' . ltrim($remotePath, '/');
        $url  = "ftp://{$this->server->host}:{$this->server->port}{$path}";

        $fp = fopen('php://temp', 'r+');
        fwrite($fp, $content);
        rewind($fp);

        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_URL, $url);
        curl_setopt($ch, \CURLOPT_USERPWD, "{$this->server->username}:{$this->server->password}");
        curl_setopt($ch, defined('CURLOPT_FTP_USE_PASV') ? \CURLOPT_FTP_USE_PASV : 119, true);
        curl_setopt($ch, \CURLOPT_UPLOAD, true);
        curl_setopt($ch, \CURLOPT_INFILE, $fp);
        curl_setopt($ch, \CURLOPT_INFILESIZE, strlen($content));
        curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, \CURLOPT_TIMEOUT, 30);

        curl_exec($ch);
        $error = curl_errno($ch);
        curl_close($ch);
        fclose($fp);

        return $error === 0;
    }

    public function listDirectory(string $path): array
    {
        if (!$this->server) {
            return [];
        }

        $dirPath    = '/' . ltrim(rtrim($path, '/'), '/') . '/';
        $candidates = array_unique([$dirPath, rtrim($dirPath, '/'), $path]);
        $listing    = null;

        foreach ($candidates as $p) {
            $ch     = $this->makeCurl($this->server, $p);
            $result = curl_exec($ch);
            $error  = curl_errno($ch);
            curl_close($ch);

            if ($error === 0 && $result !== false && trim($result) !== '') {
                $listing = $result;
                break;
            }
        }

        if ($listing === null) {
            return [];
        }

        $entries = $this->parseFullListing($listing);

        usort($entries, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'dir' ? -1 : 1;
            }
            return strcasecmp($a['name'], $b['name']);
        });

        return $entries;
    }

    public function makeDirectory(string $path): bool
    {
        return $this->runFtpCommands(['MKD ' . $this->absPath($path)]);
    }

    public function deleteFile(string $path): bool
    {
        return $this->runFtpCommands(['DELE ' . $this->absPath($path)]);
    }

    public function deleteDirectory(string $path): bool
    {
        return $this->runFtpCommands(['RMD ' . $this->absPath($path)]);
    }

    public function renameFile(string $from, string $to): bool
    {
        return $this->runFtpCommands([
            'RNFR ' . $this->absPath($from),
            'RNTO ' . $this->absPath($to),
        ]);
    }

    private function runFtpCommands(array $commands): bool
    {
        if (!$this->server) {
            return false;
        }

        $url = "ftp://{$this->server->host}:{$this->server->port}/";
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->server->username}:{$this->server->password}");
        curl_setopt($ch, defined('CURLOPT_FTP_USE_PASV') ? CURLOPT_FTP_USE_PASV : 119, true);
        curl_setopt($ch, CURLOPT_QUOTE, $commands);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_exec($ch);
        $error = curl_errno($ch);
        curl_close($ch);

        return $error === 0;
    }

    private function parseFullListing(string $listing): array
    {
        $entries = [];

        foreach (explode("\n", trim($listing)) as $line) {
            $line = trim($line);
            if ($line === '' || $line === '.' || $line === '..') {
                continue;
            }

            // Unix-style: drwxr-xr-x 2 user group 4096 May 28 12:34 dirname
            if (preg_match(
                '/^([d\-][rwxsStT\-]{9})\S*\s+\d+\s+\S+\s+\S+\s+(\d+)\s+(\w+\s+\d+\s+[\d:]+)\s+(.+)$/',
                $line, $m
            )) {
                $entries[] = [
                    'name'     => basename(trim($m[4])),
                    'type'     => $m[1][0] === 'd' ? 'dir' : 'file',
                    'size'     => (int) $m[2],
                    'modified' => trim($m[3]),
                ];
                continue;
            }

            // Windows/IIS-style: 05-29-26  02:34PM  <DIR>  dirname
            if (preg_match(
                '/^(\d{2}-\d{2}-\d{2}\s+\d{2}:\d{2}[AP]M)\s+(<DIR>|\d+)\s+(.+)$/',
                $line, $m
            )) {
                $isDir     = $m[2] === '<DIR>';
                $entries[] = [
                    'name'     => basename(trim($m[3])),
                    'type'     => $isDir ? 'dir' : 'file',
                    'size'     => $isDir ? null : (int) $m[2],
                    'modified' => trim($m[1]),
                ];
            }
        }

        return $entries;
    }

    private function absPath(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    public function disconnect(): void
    {
        $this->server = null;
    }

    public static function parseFilename(string $filename): array
    {
        $name  = pathinfo($filename, PATHINFO_FILENAME);
        $parts = explode('_', $name);

        $date    = '—';
        $session = 'Unknown';

        if (count($parts) >= 3) {
            $datePart = $parts[0];
            $timePart = $parts[1] ?? '';
            $typePart = strtoupper($parts[2] ?? '');

            if (strlen($datePart) === 6 && is_numeric($datePart)) {
                $y    = '20' . substr($datePart, 0, 2);
                $m    = substr($datePart, 2, 2);
                $d    = substr($datePart, 4, 2);
                $h    = substr($timePart, 0, 2);
                $i    = substr($timePart, 2, 2);
                $date = "$d/$m/$y $h:$i";
            }

            if ($typePart === 'R') {
                $session = 'Race';
            } elseif ($typePart === 'Q') {
                $session = 'Qualifying';
            }
        }

        return compact('date', 'session');
    }
}