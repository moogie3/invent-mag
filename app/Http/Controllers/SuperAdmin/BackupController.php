<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function index()
    {
        $backups = [];
        $backupPath = storage_path('app/backups');
        if (is_dir($backupPath)) {
            $files = array_diff(scandir($backupPath), ['.', '..']);
            foreach ($files as $file) {
                $path = $backupPath . DIRECTORY_SEPARATOR . $file;
                $backups[] = [
                    'name' => $file,
                    'size' => filesize($path),
                    'modified' => filemtime($path),
                ];
            }
            usort($backups, fn($a, $b) => $b['modified'] <=> $a['modified']);
        }

        return view('superadmin.backups.index', compact('backups'));
    }
}
