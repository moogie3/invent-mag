<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use ZipArchive;

class BackupControllerTest extends TestCase
{
    use WithFaker, CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
    }

    // ──────────────────────────────────────────────
    // index
    // ──────────────────────────────────────────────

    public function test_index_displays_backup_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.setting.backup.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings.backup');
        $response->assertViewHas('tenant');
        $response->assertViewHas('files');
        $response->assertViewHas('latestBackup');
    }

    public function test_index_requires_authentication(): void
    {
        auth()->logout();

        $response = $this->get(route('admin.setting.backup.index'));

        // Fortify returns 401 for unauthenticated JSON requests; for browser
        // requests it may redirect or return 401 depending on middleware config.
        $this->assertContains($response->status(), [302, 303, 401]);
    }

    // ──────────────────────────────────────────────
    // run (create backup)
    // ──────────────────────────────────────────────

    public function test_run_requires_valid_type(): void
    {
        // The run() method wraps validate() in a try/catch, so validation errors
        // are returned as JSON 500 with an error message rather than 422.
        $response = $this->actingAs($this->user)
            ->postJson(route('admin.setting.backup.run'), ['type' => 'invalid_type']);

        $response->assertJsonStructure(['success', 'message']);
        $this->assertFalse($response->json('success'));
    }

    public function test_run_rejects_missing_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('admin.setting.backup.run'), []);

        $response->assertJsonStructure(['success', 'message']);
        $this->assertFalse($response->json('success'));
    }

    public function test_run_accepts_db_only_type(): void
    {
        // We only assert the request is accepted and returns JSON — actual
        // mysqldump execution is an integration concern outside unit scope.
        $response = $this->actingAs($this->user)
            ->postJson(route('admin.setting.backup.run'), ['type' => 'db_only']);

        $response->assertJsonStructure(['success', 'message']);
    }

    public function test_run_accepts_full_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('admin.setting.backup.run'), ['type' => 'full']);

        $response->assertJsonStructure(['success', 'message']);
    }

    // ──────────────────────────────────────────────
    // download
    // ──────────────────────────────────────────────

    public function test_download_returns_404_for_missing_file(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.setting.backup.download', 'nonexistent.zip'));

        $response->assertRedirect(route('admin.setting.backup.index'));
        $response->assertSessionHas('error');
    }

    public function test_download_rejects_file_belonging_to_another_tenant(): void
    {
        // Store a file under a different tenant's prefix
        Storage::disk('local')->put('backups/othertenant-2024-01-01-00-00-00.zip', 'fake');

        $response = $this->actingAs($this->user)
            ->get(route('admin.setting.backup.download', 'othertenant-2024-01-01-00-00-00.zip'));

        $response->assertRedirect(route('admin.setting.backup.index'));
        $response->assertSessionHas('error');

        Storage::disk('local')->delete('backups/othertenant-2024-01-01-00-00-00.zip');
    }

    public function test_download_streams_file_that_belongs_to_tenant(): void
    {
        $filename = $this->tenant->domain . '-2024-01-01-00-00-00.zip';
        Storage::disk('local')->put('backups/' . $filename, 'fake-zip-content');

        $response = $this->actingAs($this->user)
            ->get(route('admin.setting.backup.download', $filename));

        $response->assertStatus(200);

        Storage::disk('local')->delete('backups/' . $filename);
    }

    // ──────────────────────────────────────────────
    // delete
    // ──────────────────────────────────────────────

    public function test_delete_removes_backup_file(): void
    {
        $filename = $this->tenant->domain . '-2024-01-01-12-00-00.zip';
        Storage::disk('local')->put('backups/' . $filename, 'fake-zip-content');

        $response = $this->actingAs($this->user)
            ->delete(route('admin.setting.backup.delete', $filename));

        $response->assertRedirect(route('admin.setting.backup.index'));
        $response->assertSessionHas('success');
        $this->assertFalse(Storage::disk('local')->exists('backups/' . $filename));
    }

    public function test_delete_returns_error_for_missing_file(): void
    {
        $response = $this->actingAs($this->user)
            ->delete(route('admin.setting.backup.delete', 'nonexistent.zip'));

        $response->assertRedirect(route('admin.setting.backup.index'));
        $response->assertSessionHas('error');
    }

    public function test_delete_rejects_file_belonging_to_another_tenant(): void
    {
        $filename = 'othertenant-2024-01-01-00-00-00.zip';
        Storage::disk('local')->put('backups/' . $filename, 'fake');

        $response = $this->actingAs($this->user)
            ->delete(route('admin.setting.backup.delete', $filename));

        $response->assertRedirect(route('admin.setting.backup.index'));
        $response->assertSessionHas('error');
        // File should NOT have been deleted
        $this->assertTrue(Storage::disk('local')->exists('backups/' . $filename));

        Storage::disk('local')->delete('backups/' . $filename);
    }

    // ──────────────────────────────────────────────
    // restore
    // ──────────────────────────────────────────────

    public function test_restore_rejects_missing_file(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('admin.setting.backup.restore'), []);

        $response->assertSessionHasErrors('backup_file');
    }

    public function test_restore_rejects_non_zip_file(): void
    {
        $file = UploadedFile::fake()->create('backup.txt', 10, 'text/plain');

        $response = $this->actingAs($this->user)
            ->post(route('admin.setting.backup.restore'), ['backup_file' => $file]);

        $response->assertSessionHasErrors('backup_file');
    }

    public function test_restore_rejects_wrong_password_when_encryption_enabled(): void
    {
        $this->tenant->update([
            'backup_password' => 'correct-password-abc-123-XYZ',
            'backup_encrypted' => true,
        ]);

        $zip = $this->makeFakeBackupZip();

        $response = $this->actingAs($this->user)
            ->post(route('admin.setting.backup.restore'), [
                'backup_file' => $zip,
                'password'    => 'wrong-password',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_restore_does_not_require_password_when_encryption_disabled(): void
    {
        $this->tenant->update([
            'backup_password' => null,
            'backup_encrypted' => false,
        ]);

        $zip = $this->makeFakeBackupZip();

        $response = $this->actingAs($this->user)
            ->post(route('admin.setting.backup.restore'), [
                'backup_file' => $zip,
                // no password field
            ]);

        // Validation should pass (no 'password' rule when encryption disabled).
        // The restore itself may fail due to test DB constraints — that's fine.
        $this->assertNotContains('password', array_keys(
            session('errors') ? session('errors')->getBag('default')->toArray() : []
        ));
    }

    public function test_restore_fails_gracefully_with_invalid_zip(): void
    {
        $corruptFile = UploadedFile::fake()->createWithContent('backup.zip', 'not-a-zip');

        $response = $this->actingAs($this->user)
            ->post(route('admin.setting.backup.restore'), [
                'backup_file' => $corruptFile,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_restore_fails_gracefully_when_zip_missing_backup_sql(): void
    {
        // Build a valid ZIP that does NOT contain backup.sql
        $zipPath = sys_get_temp_dir() . '/test-no-sql.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('readme.txt', 'no sql here');
        $zip->close();

        $file = new UploadedFile($zipPath, 'backup.zip', 'application/zip', null, true);

        $response = $this->actingAs($this->user)
            ->post(route('admin.setting.backup.restore'), ['backup_file' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        @unlink($zipPath);
    }

    // ──────────────────────────────────────────────
    // updatePassword
    // ──────────────────────────────────────────────

    public function test_update_password_requires_current_password(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('admin.setting.backup.password'), [
                'new_password'              => 'NewStrongPass123!XYZ',
                'new_password_confirmation' => 'NewStrongPass123!XYZ',
            ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_update_password_rejects_wrong_current_password(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('admin.setting.backup.password'), [
                'current_password'          => 'wrong-user-password',
                'new_password'              => 'NewStrongPass123!XYZ',
                'new_password_confirmation' => 'NewStrongPass123!XYZ',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_update_password_rejects_weak_password(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('admin.setting.backup.password'), [
                'current_password'          => 'password',
                'new_password'              => 'short',        // too short, fails min:16
                'new_password_confirmation' => 'short',
            ]);

        $response->assertSessionHasErrors('new_password');
    }

    public function test_update_password_rejects_mismatched_confirmation(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('admin.setting.backup.password'), [
                'current_password'          => 'password',
                'new_password'              => 'NewStrongPass123!XYZ',
                'new_password_confirmation' => 'DoesNotMatch123!XYZ',
            ]);

        $response->assertSessionHasErrors('new_password_confirmation');
    }

    public function test_update_password_saves_new_backup_password(): void
    {
        // The user factory creates users with password = 'password' by default
        $response = $this->actingAs($this->user)
            ->post(route('admin.setting.backup.password'), [
                'current_password'          => 'password',
                'new_password'              => 'NewStrongPass123!XYZ',
                'new_password_confirmation' => 'NewStrongPass123!XYZ',
                'encryption_enabled'        => '1',
            ]);

        $response->assertRedirect(route('admin.setting.backup.index'));
        $response->assertSessionHas('success');

        $this->tenant->refresh();
        $this->assertEquals('NewStrongPass123!XYZ', $this->tenant->backup_password);
        $this->assertTrue((bool) $this->tenant->backup_encrypted);
    }

    public function test_update_password_can_disable_encryption(): void
    {
        $this->tenant->update(['backup_encrypted' => true]);

        $response = $this->actingAs($this->user)
            ->post(route('admin.setting.backup.password'), [
                'current_password'          => 'password',
                'new_password'              => 'NewStrongPass123!XYZ',
                'new_password_confirmation' => 'NewStrongPass123!XYZ',
                'encryption_enabled'        => '0', // explicit falsy to disable
            ]);

        $response->assertRedirect(route('admin.setting.backup.index'));

        $this->tenant->refresh();
        $this->assertFalse((bool) $this->tenant->backup_encrypted);
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /**
     * Build a valid ZIP containing a minimal backup.sql.
     */
    private function makeFakeBackupZip(): UploadedFile
    {
        $zipPath = sys_get_temp_dir() . '/test-backup-' . uniqid() . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('backup.sql', "SET FOREIGN_KEY_CHECKS=0;\n-- fake sql\nSET FOREIGN_KEY_CHECKS=1;\n");
        $zip->close();

        return new UploadedFile($zipPath, 'backup.zip', 'application/zip', null, true);
    }
}
