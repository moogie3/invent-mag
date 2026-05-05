<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
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
        $domainPrefix = $tenant->domain . '-';
        
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
                    'last_modified' => \Carbon\Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file)),
                ];
            })->sortByDesc('last_modified')->values()->toArray();
    }

    public function index()
    {
        $tenant = app('currentTenant');
        $files = $this->getBackupFiles();
        $latestBackup = !empty($files) ? $files[0] : null;

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
            $filename = $tenant->domain . '-' . now()->format('Y-m-d-H-i-s') . '.zip';
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
            $backupPath = 'backups/' . $filename;
            if (Storage::disk('local')->exists($backupPath)) {
                return response()->json([
                    'success' => true, 
                    'message' => __('messages.backup_success')
                ]);
            }
            
            // If failed, see if it's mysqldump
            if (str_contains(strtolower($output . $errorOutput), 'mysqldump')) {
                return response()->json([
                    'success' => false, 
                    'warning' => true, 
                    'message' => __('messages.backup_info_local')
                ]);
            }
            
            return response()->json([
                'success' => false, 
                'message' => __('messages.backup_error') . ': ' . ($errorOutput ?: 'Unknown error')
            ], 500);
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function download(string $filename)
    {
        $tenant = app('currentTenant');
        $path = $this->getBackupPath() . '/' . $filename;

        // Security: Verify file belongs to this tenant
        if (!Storage::disk('local')->exists($path)) {
            return redirect()->route('admin.setting.backup.index')
                ->with('error', __('messages.backup_file_not_found'));
        }

        // Verify filename starts with tenant domain (additional security)
        if (!str_starts_with($filename, $tenant->domain)) {
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
        $path = $this->getBackupPath() . '/' . $filename;

        // Security: Verify file belongs to this tenant
        if (!Storage::disk('local')->exists($path)) {
            return redirect()->route('admin.setting.backup.index')
                ->with('error', __('messages.backup_file_not_found'));
        }

        // Verify filename starts with tenant domain (additional security)
        if (!str_starts_with($filename, $tenant->domain)) {
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
        $request->validate([
            'backup_file' => 'required|file|mimes:zip',
            'password' => 'required|string',
        ], [
            'backup_file.required' => __('messages.backup_file_required'),
            'backup_file.file' => __('messages.backup_file_must_be_file'),
            'backup_file.mimes' => __('messages.backup_file_must_be_zip'),
            'password.required' => __('messages.backup_restore_password_required'),
        ]);

        $tenant = app('currentTenant');
        $file = $request->file('backup_file');
        $password = $request->input('password');

        // Verify password for encrypted backup
        if ($tenant->isBackupEncrypted()) {
            if ($password !== $tenant->backup_password) {
                return redirect()->back()->with('error', __('messages.backup_password_error'));
            }
        }

        try {
            // Store the uploaded file temporarily
            $tempPath = $file->store('backup-restore');
            
            Log::channel('security')->info('Backup restore attempted', [
                'tenant_id' => $tenant->id,
                'filename' => $file->getClientOriginalName(),
            ]);

            // Note: Full restore implementation would require the stefanzweifel/laravel-backup-restore package
            // For now, show info message
            Storage::disk('local')->delete($tempPath);

            return redirect()->route('admin.setting.backup.index')
                ->with('info', __('messages.backup_restore_info'));
        } catch (\Exception $e) {
            return redirect()->route('admin.setting.backup.index')
                ->with('error', __('messages.backup_restore_error') . ': ' . $e->getMessage());
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
        if (!Hash::check($request->current_password, $user->password)) {
            Log::channel('security')->warning('Failed password change attempt', [
                'user_id' => $user->id,
                'email' => $user->email,
                'reason' => 'Incorrect current password',
            ]);
            return redirect()->back()->with('error', __('messages.backup_password_error'));
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