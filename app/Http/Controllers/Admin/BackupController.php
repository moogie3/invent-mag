<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    protected function getBackupPath(): string
    {
        return 'backups';
    }

    protected function getBackupFiles(): array
    {
        $path = 'backups';
        $tenant = app('currentTenant');
        $domainPrefix = $tenant->domain.'-';

        $allFiles = Storage::disk('local')->files($path);

        return collect($allFiles)
            ->filter(function ($file) use ($domainPrefix) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'zip'
                    && str_starts_with(basename($file), $domainPrefix);
            })
            ->map(function ($file) {
                return [
                    'filename' => basename($file),
                    'path' => $file,
                    'size' => Storage::disk('local')->size($file),
                    'last_modified' => Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file)),
                ];
            })->sortByDesc('last_modified')->values()->toArray();
    }

    public function index()
    {
        $tenant = app('currentTenant');
        $files = $this->getBackupFiles();
        $latestBackup = ! empty($files) ? $files[0] : null;

        return view('admin.settings.backup', compact('tenant', 'files', 'latestBackup'));
    }

    public function run(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:db_only,full',
            ]);

            $tenant = app('currentTenant');
            $type = $request->input('type', 'db_only');
            $filename = $tenant->domain.'-'.now()->format('Y-m-d-H-i-s').'.zip';
            $tenantId = $tenant->getKey();

            $artisan = base_path('artisan');
            $backupCmd = 'backup:tenant';

            $cmd = ['php', $artisan, $backupCmd, $tenantId, $type, $filename];

            $process = new Process($cmd);
            $process->setWorkingDirectory(base_path());

            // Set password via environment variable to isolate config
            $env = [];
            if ($tenant->isBackupEncrypted()) {
                $env['BACKUP_ARCHIVE_PASSWORD'] = $tenant->backup_password;
            }

            $process->setEnv($env);
            $process->setTimeout(600); // 10 minutes max
            $process->run();

            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            // Check if the specific backup file was created
            $backupPath = 'backups/'.$filename;
            if (Storage::disk('local')->exists($backupPath)) {
                return response()->json([
                    'success' => true,
                    'message' => __('messages.backup_success'),
                ]);
            }

            // If failed, see if it's mysqldump
            if (str_contains(strtolower($output.$errorOutput), 'mysqldump')) {
                return response()->json([
                    'success' => false,
                    'warning' => true,
                    'message' => __('messages.backup_info_local'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => __('messages.backup_error').': '.($errorOutput ?: 'Unknown error'),
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function download(string $filename)
    {
        $tenant = app('currentTenant');
        $path = $this->getBackupPath().'/'.$filename;

        // Security: Verify file belongs to this tenant
        if (! Storage::disk('local')->exists($path)) {
            return redirect()->route('admin.setting.backup.index')
                ->with('error', __('messages.backup_file_not_found'));
        }

        // Verify filename starts with tenant domain (additional security)
        if (! str_starts_with($filename, $tenant->domain)) {
            return redirect()->route('admin.setting.backup.index')
                ->with('error', __('messages.backup_unauthorized'));
        }

        Log::channel('security')->info('Backup downloaded', [
            'tenant_id' => $tenant->id,
            'filename' => $filename,
        ]);

        return Storage::disk('local')->download($path, $filename);
    }

    public function delete(string $filename)
    {
        $tenant = app('currentTenant');
        $path = $this->getBackupPath().'/'.$filename;

        // Security: Verify file belongs to this tenant
        if (! Storage::disk('local')->exists($path)) {
            return redirect()->route('admin.setting.backup.index')
                ->with('error', __('messages.backup_file_not_found'));
        }

        // Verify filename starts with tenant domain (additional security)
        if (! str_starts_with($filename, $tenant->domain)) {
            return redirect()->route('admin.setting.backup.index')
                ->with('error', __('messages.backup_unauthorized'));
        }

        try {
            Storage::disk('local')->delete($path);

            Log::channel('security')->info('Backup deleted', [
                'tenant_id' => $tenant->id,
                'filename' => $filename,
            ]);

            return redirect()->route('admin.setting.backup.index')
                ->with('success', __('messages.backup_delete_success'));
        } catch (\Exception $e) {
            return redirect()->route('admin.setting.backup.index')
                ->with('error', __('messages.backup_delete_error'));
        }
    }

    public function restore(Request $request)
    {
        $tenant = app('currentTenant');

        Log::channel('security')->info('Restore attempt started', [
            'tenant_id' => $tenant->id,
            'is_encrypted' => $tenant->isBackupEncrypted(),
            'has_file' => $request->hasFile('backup_file'),
            'has_password' => $request->has('password'),
        ]);

        // Password only required if encryption is enabled on the tenant
        $rules = [
            'backup_file' => 'required|file|mimes:zip',
        ];

        $messages = [
            'backup_file.required' => __('messages.backup_file_required'),
            'backup_file.file' => __('messages.backup_file_must_be_file'),
            'backup_file.mimes' => __('messages.backup_file_must_be_zip'),
        ];

        if ($tenant->isBackupEncrypted()) {
            $rules['password'] = 'required|string';
            $messages['password.required'] = __('messages.backup_restore_password_required');
        }

        $validated = $request->validate($rules, $messages);

        $file = $request->file('backup_file');
        $password = $request->input('password');

        Log::channel('security')->info('Restore validation passed', [
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);

        // Verify password for encrypted backup
        if ($tenant->isBackupEncrypted()) {
            if ($password !== $tenant->backup_password) {
                return redirect()->back()->withInput()
                    ->with('error', __('messages.backup_password_error'));
            }
        }

        try {
            // Store the uploaded file temporarily
            $tempPath = $file->store('backup-restore');

            Log::channel('security')->info('Backup restore file stored', [
                'tenant_id' => $tenant->id,
                'temp_path' => $tempPath,
                'filename' => $file->getClientOriginalName(),
                'encrypted' => $tenant->isBackupEncrypted(),
            ]);

            // Open and validate the ZIP
            $zipPath = Storage::disk('local')->path($tempPath);
            $zip = new \ZipArchive;
            $openResult = $zip->open($zipPath);

            if ($openResult !== true) {
                Storage::disk('local')->delete($tempPath);
                Log::error('Restore failed: Invalid ZIP', ['result' => $openResult]);

                return redirect()->back()->withInput()
                    ->with('error', __('messages.backup_restore_error').': Invalid or corrupted ZIP file');
            }

            // Find backup.sql inside the ZIP
            $sqlIndex = $zip->locateName('backup.sql');
            if ($sqlIndex === false) {
                $zip->close();
                Storage::disk('local')->delete($tempPath);

                return redirect()->back()->withInput()
                    ->with('error', __('messages.backup_restore_error').': backup.sql not found inside ZIP');
            }

            // Extract SQL content from ZIP
            $sqlContent = $zip->getFromIndex($sqlIndex);
            $zip->close();

            // Clean up ZIP temp file
            Storage::disk('local')->delete($tempPath);

            if (empty($sqlContent)) {
                return redirect()->back()->withInput()
                    ->with('error', __('messages.backup_restore_error').': backup.sql is empty');
            }

            // Execute the SQL to restore tenant data
            $dbConfig = config('database.connections.mysql');
            $tenantId = $tenant->getKey();
            $mysqlPath = $dbConfig['dump']['dump_binary_path'] ?? 'C:/xampp/mysql/bin';
            $host = $dbConfig['host'];
            $port = $dbConfig['port'];
            $username = $dbConfig['username'];
            $dbPassword = $dbConfig['password'];
            $database = $dbConfig['database'];

            // Write SQL to a temp file for mysql CLI
            $sqlTempPath = storage_path('app/backup-restore/restore_'.$tenantId.'_'.time().'.sql');
            if (! is_dir(dirname($sqlTempPath))) {
                mkdir(dirname($sqlTempPath), 0755, true);
            }
            file_put_contents($sqlTempPath, $sqlContent);

            // Build mysql command
            $cmd = [
                "{$mysqlPath}/mysql.exe",
                '-h', $host,
                '-P', (string) $port,
                '-u', $username,
            ];
            if ($dbPassword) {
                $cmd[] = '-p'.$dbPassword;
            }
            $cmd[] = $database;

            $process = new Process($cmd);
            $process->setWorkingDirectory(base_path());
            $process->setInput(file_get_contents($sqlTempPath));
            $process->setTimeout(300);
            $process->run();

            // Clean up SQL temp file
            @unlink($sqlTempPath);

            if (! $process->isSuccessful()) {
                $error = $process->getErrorOutput();
                Log::error('Restore SQL execution failed', ['error' => $error, 'tenant_id' => $tenantId]);

                return redirect()->route('admin.setting.backup.index')
                    ->with('error', __('messages.backup_restore_error').': '.$error);
            }

            Log::channel('security')->info('Backup restored successfully', [
                'tenant_id' => $tenantId,
                'filename' => $file->getClientOriginalName(),
            ]);

            return redirect()->route('admin.setting.backup.index')
                ->with('success', __('messages.backup_restore_success') ?? 'Backup restored successfully. Your data has been replaced with the backup.');

        } catch (\Exception $e) {
            Log::error('Restore exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return redirect()->route('admin.setting.backup.index')
                ->with('error', __('messages.backup_restore_error').': '.$e->getMessage());
        }
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'min:16',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&_\-!#$%^&*(){}|<>?]+$/',
            ],
            'new_password_confirmation' => 'required|string|same:new_password',
            'encryption_enabled' => 'sometimes|boolean',
        ], [
            'current_password.required' => __('messages.backup_password_current_required'),
            'new_password.required' => __('messages.backup_password_new_required'),
            'new_password.min' => __('messages.backup_password_min'),
            'new_password.regex' => __('messages.backup_password_strength'),
            'new_password_confirmation.required' => __('messages.backup_password_confirm_required'),
            'new_password_confirmation.same' => __('messages.backup_passwords_do_not_match'),
        ]);

        $tenant = app('currentTenant');
        $user = Auth::user();

        // Verify current password against user's password
        if (! Hash::check($request->current_password, $user->password)) {
            Log::channel('security')->warning('Failed password change attempt', [
                'user_id' => $user->id,
                'email' => $user->email,
                'reason' => 'Incorrect current password',
            ]);

            return redirect()->back()
                ->with('error', __('messages.backup_password_error'));
        }

        $newPassword = $request->new_password;
        $encryptionEnabled = $request->boolean('encryption_enabled', $tenant->backup_encrypted ?? false);

        // Update tenant backup settings
        $tenant->update([
            'backup_password' => $newPassword,
            'backup_encrypted' => $encryptionEnabled,
        ]);

        Log::channel('security')->info('Backup password changed', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        return redirect()->route('admin.setting.backup.index')
            ->with('success', __('messages.backup_password_changed'));
    }
}
