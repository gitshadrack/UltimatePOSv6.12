<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;

class SystemDownloadController extends Controller
{
    private const PRINT_SERVER_FILE = 'tools/windows-print-server/artifacts/UltimatePOS-PrintServer-Setup.exe';

    private const PRINT_SERVER_REPOSITORY_URL = 'https://github.com/gitshadrack/UltimatePOSv6.12/raw/main/tools/windows-print-server/artifacts/UltimatePOS-PrintServer-Setup.exe';

    public function index()
    {
        $path = $this->printServerPath();
        $available = is_file($path);
        $modifiedAt = $available ? (int) filemtime($path) : null;
        $version = $this->printServerVersion();

        $download = [
            'name' => 'UltimatePOS Print Server for Windows',
            'file_name' => basename(self::PRINT_SERVER_FILE),
            'version' => $version,
            'available' => $available,
            'size' => $available ? number_format(filesize($path) / 1048576, 1).' MB' : null,
            'modified_at' => $modifiedAt,
            'sha256' => $available
                ? Cache::remember(
                    'system-download.print-server.sha256.'.$modifiedAt,
                    now()->addDay(),
                    fn () => hash_file('sha256', $path)
                )
                : null,
            'repository_url' => self::PRINT_SERVER_REPOSITORY_URL,
        ];

        return view('system_downloads.index', compact('download'));
    }

    public function printServer()
    {
        $path = $this->printServerPath();
        abort_unless(is_file($path), 404, 'The Print Server installer is not available on this deployment.');

        return response()->download($path, basename(self::PRINT_SERVER_FILE), [
            'Content-Type' => 'application/vnd.microsoft.portable-executable',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function printServerPath(): string
    {
        return base_path(self::PRINT_SERVER_FILE);
    }

    private function printServerVersion(): string
    {
        $script = base_path('tools/windows-print-server/UltimatePOS-PrintServer-Setup.iss');
        if (! is_file($script)) {
            return 'Current';
        }

        $contents = file_get_contents($script);
        if ($contents !== false && preg_match('/^AppVersion=(.+)$/m', $contents, $matches)) {
            return trim($matches[1]);
        }

        return 'Current';
    }
}
