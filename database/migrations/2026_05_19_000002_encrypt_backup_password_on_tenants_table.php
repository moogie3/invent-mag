<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    public function up(): void
    {
        // Widen the column from varchar(100) to text to accommodate encrypted values (~228 chars)
        Schema::table('tenants', function (Blueprint $table) {
            $table->text('backup_password')->nullable()->change();
        });

        // Re-encrypt any existing plaintext passwords
        // A plaintext password cannot be decrypted, so we detect non-encrypted values
        // by checking if they fail decryption (encrypted values start with 'eyJ')
        $tenants = DB::table('tenants')->whereNotNull('backup_password')->get(['id', 'backup_password']);

        foreach ($tenants as $tenant) {
            $value = $tenant->backup_password;

            // Laravel encrypted strings are base64-encoded JSON starting with 'eyJ'
            // If decryption fails, it's still plaintext
            try {
                Crypt::decryptString($value);
                // Already encrypted — skip
            } catch (\Exception $e) {
                // Plaintext — encrypt it now
                DB::table('tenants')->where('id', $tenant->id)->update([
                    'backup_password' => Crypt::encryptString($value),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Decrypt all values back to plaintext before shrinking the column
        $tenants = DB::table('tenants')->whereNotNull('backup_password')->get(['id', 'backup_password']);

        foreach ($tenants as $tenant) {
            try {
                $plain = Crypt::decryptString($tenant->backup_password);
                DB::table('tenants')->where('id', $tenant->id)->update([
                    'backup_password' => substr($plain, 0, 100),
                ]);
            } catch (\Exception $e) {
                // Already plaintext, nothing to do
            }
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('backup_password', 100)->nullable()->change();
        });
    }
};
