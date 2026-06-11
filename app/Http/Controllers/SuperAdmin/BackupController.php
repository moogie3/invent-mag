<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    public function index()
    {
        $tenants = Tenant::whereNotNull('plan_id')->orderBy('name')->get(['id', 'name', 'domain', 'plan_status', 'backup_encrypted']);
        $systemBackups = $this->getSystemBackups();
        $tenantBackups = $this->getTenantBackupFiles();

        $stats = $this->getBackupStats($systemBackups, $tenantBackups);

        return view('superadmin.backups.index', compact('tenants', 'systemBackups', 'tenantBackups', 'stats'));
    }

    public function create()
    {
        $exitCode = Artisan::call('backup:run');

        if ($exitCode === 0) {
            return redirect()->route('superadmin.backups.index')
                ->with('success', 'System backup created successfully.');
        }

        return redirect()->route('superadmin.backups.index')
            ->with('error', 'System backup creation failed. Check logs for details.');
    }

    public function createTenant(Request $request)
    {
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'type' => 'required|in:db_only,full',
        ]);

        $tenant = Tenant::findOrFail($request->tenant_id);
        $type = $request->type;
        $filename = $tenant->domain . '-' . now()->format('Y-m-d-H-i-s') . '.zip';
        $tenantId = $tenant->getKey();

        try {
            $artisan = base_path('artisan');
            $backupCmd = 'backup:tenant';

            $cmd = ['php', $artisan, $backupCmd, $tenantId, $type, $filename];

            $process = new Process($cmd);
            $process->setWorkingDirectory(base_path());
            $process->setTimeout(600);
            $process->run();

            $backupPath = 'backups/' . $filename;
            if (Storage::disk('local')->exists($backupPath)) {
                Log::info("Super admin created tenant backup for {$tenant->name}: {$filename}");

                return redirect()->route('superadmin.backups.index')
                    ->with('success', "Backup created for '{$tenant->name}' ({$type}).");
            }

            $errorOutput = $process->getErrorOutput();
            return redirect()->route('superadmin.backups.index')
                ->with('error', 'Backup creation failed: ' . ($errorOutput ?: 'Unknown error'));
        } catch (\Exception $e) {
            return redirect()->route('superadmin.backups.index')
                ->with('error', 'Backup creation failed: ' . $e->getMessage());
        }
    }

    public function download($filename)
    {
        $path = storage_path('app/backups/' . basename($filename));

        if (!file_exists($path)) {
            return redirect()->route('superadmin.backups.index')
                ->with('error', 'Backup file not found.');
        }

        return response()->download($path);
    }

    public function destroy($filename)
    {
        $path = storage_path('app/backups/' . basename($filename));

        if (!file_exists($path)) {
            return redirect()->route('superadmin.backups.index')
                ->with('error', 'Backup file not found.');
        }

        unlink($path);

        Log::info("Backup deleted by super admin: {$filename}");

        return redirect()->route('superadmin.backups.index')
            ->with('success', 'Backup deleted successfully.');
    }

    public function restore(Request $request)
    {
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'backup_file' => 'required|file|mimes:zip',
            'password' => 'nullable|string',
        ]);

        $tenant = Tenant::findOrFail($request->tenant_id);
        $file = $request->file('backup_file');
        $password = $request->input('password');

        if ($tenant->isBackupEncrypted()) {
            if (!$password) {
                return redirect()->route('superadmin.backups.index')
                    ->with('error', 'Password is required for encrypted backup restoration.')
                    ->withInput();
            }
            if ($password !== $tenant->backup_password) {
                return redirect()->route('superadmin.backups.index')
                    ->with('error', 'Incorrect backup password.')
                    ->withInput();
            }
        }

        try {
            $tempPath = $file->store('backup-restore');

            Log::channel('security')->info('Super admin restore attempt', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'filename' => $file->getClientOriginalName(),
            ]);

            $zipPath = Storage::disk('local')->path($tempPath);
            $zip = new \ZipArchive();
            $openResult = $zip->open($zipPath);

            if ($openResult !== true) {
                Storage::disk('local')->delete($tempPath);
                return redirect()->route('superadmin.backups.index')
                    ->with('error', 'Invalid or corrupted ZIP file.');
            }

            $sqlIndex = $zip->locateName('backup.sql');
            if ($sqlIndex === false) {
                $zip->close();
                Storage::disk('local')->delete($tempPath);
                return redirect()->route('superadmin.backups.index')
                    ->with('error', 'backup.sql not found inside the ZIP file.');
            }

            $sqlContent = $zip->getFromIndex($sqlIndex);
            $zip->close();
            Storage::disk('local')->delete($tempPath);

            if (empty($sqlContent)) {
                return redirect()->route('superadmin.backups.index')
                    ->with('error', 'backup.sql is empty.');
            }

            $dbConfig = config('database.connections.mysql');
            $mysqlPath = $dbConfig['dump']['dump_binary_path'] ?? 'C:/xampp/mysql/bin';
            $host = $dbConfig['host'];
            $port = $dbConfig['port'];
            $username = $dbConfig['username'];
            $dbPassword = $dbConfig['password'];
            $database = $dbConfig['database'];

            $sqlTempPath = storage_path('app/backup-restore/restore_' . $tenant->getKey() . '_' . time() . '.sql');
            if (!is_dir(dirname($sqlTempPath))) {
                mkdir(dirname($sqlTempPath), 0755, true);
            }
            file_put_contents($sqlTempPath, $sqlContent);

            $cmd = [
                "{$mysqlPath}/mysql.exe",
                '-h', $host,
                '-P', (string) $port,
                '-u', $username,
            ];
            if ($dbPassword) {
                $cmd[] = '-p' . $dbPassword;
            }
            $cmd[] = $database;

            $process = new Process($cmd);
            $process->setWorkingDirectory(base_path());
            $process->setInput(file_get_contents($sqlTempPath));
            $process->setTimeout(300);
            $process->run();

            @unlink($sqlTempPath);

            if (!$process->isSuccessful()) {
                $error = $process->getErrorOutput();
                Log::error('Super admin restore SQL execution failed', ['error' => $error, 'tenant_id' => $tenant->id]);
                return redirect()->route('superadmin.backups.index')
                    ->with('error', 'Restore failed: ' . $error);
            }

            Log::channel('security')->info('Super admin restore completed', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'filename' => $file->getClientOriginalName(),
            ]);

            return redirect()->route('superadmin.backups.index')
                ->with('success', "Data restored for '{$tenant->name}' successfully.");
        } catch (\Exception $e) {
            Log::error('Super admin restore exception', ['error' => $e->getMessage()]);
            return redirect()->route('superadmin.backups.index')
                ->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }

    private function getSystemBackups(): array
    {
        $backups = [];
        $backupPath = storage_path('app/backups');
        if (is_dir($backupPath)) {
            $files = array_diff(scandir($backupPath), ['.', '..']);
            foreach ($files as $file) {
                $path = $backupPath . DIRECTORY_SEPARATOR . $file;
                if (!is_file($path)) continue;
                $backups[] = [
                    'name' => $file,
                    'size' => filesize($path),
                    'modified' => filemtime($path),
                ];
            }
            usort($backups, fn($a, $b) => $b['modified'] <=> $a['modified']);
        }
        return $backups;
    }

    private function getTenantBackupFiles(): array
    {
        $path = 'backups';
        $allFiles = Storage::disk('local')->files($path);
        $tenants = Tenant::whereNotNull('domain')->pluck('name', 'domain');

        return collect($allFiles)
            ->filter(function ($file) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'zip';
            })
            ->map(function ($file) use ($tenants) {
                $basename = basename($file);
                $domain = explode('-', $basename)[0] ?? null;
                $tenantName = $domain && isset($tenants[$domain]) ? $tenants[$domain] : ($domain ?? 'Unknown');

                return [
                    'filename' => $basename,
                    'path' => $file,
                    'size' => Storage::disk('local')->size($file),
                    'last_modified' => Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file)),
                    'tenant_name' => $tenantName,
                    'tenant_domain' => $domain,
                ];
            })
            ->sortByDesc('last_modified')
            ->values()
            ->toArray();
    }

    private function getBackupStats(array $systemBackups, array $tenantBackups): array
    {
        $allBackups = array_merge($systemBackups, $tenantBackups);
        $totalSize = 0;
        foreach ($allBackups as $b) {
            $totalSize += $b['size'] ?? 0;
        }

        $latestBackup = !empty($allBackups) ? reset($allBackups) : null;

        return [
            'total_files' => count($allBackups),
            'system_count' => count($systemBackups),
            'tenant_count' => count($tenantBackups),
            'total_size' => $totalSize,
            'latest' => $latestBackup ? ($latestBackup['name'] ?? $latestBackup['filename'] ?? 'N/A') : 'None',
        ];
    }
}
