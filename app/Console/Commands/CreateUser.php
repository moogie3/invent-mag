<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Standalone installs have no open registration (Fortify's registration
 * feature is off on purpose — it would hand superuser to anyone who can
 * reach the URL), so this is the supported way to add an account.
 *
 * The account is created already email-verified: there is no mailer
 * pointed at anything real, and an unverified user would be bounced to
 * /admin/email/verify on every web login with no way to proceed.
 */
class CreateUser extends Command
{
    protected $signature = 'user:create
        {email : Login address for the new account}
        {--name= : Display name, defaults to the part before the @}
        {--password= : Plain-text password; a random one is generated when omitted}
        {--role=superuser : Role to assign (web guard)}';

    protected $description = 'Create a verified user account in the standalone install';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));

        // Same rule CreateNewUser applies, so the command accepts exactly the
        // addresses the app itself would — filter_var() is stricter and rejects
        // the seeded admin@localhost convention.
        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'string', 'email', 'max:255']],
        );

        if ($validator->fails()) {
            $this->error("\"{$email}\" is not a valid email address.");

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error("A user with {$email} already exists.");

            return self::FAILURE;
        }

        $tenant = Tenant::query()->orderBy('id')->first();

        if (! $tenant) {
            $this->error('No tenant found — run the database seeders first.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: Str::before($email, '@');
        $password = $this->option('password') ?: Str::random(16);
        $roleName = (string) $this->option('role');

        $user = $tenant->execute(function () use ($email, $name, $password, $roleName) {
            // password, email_verified_at and tenant_id are all $guarded, so
            // they cannot go through create() — set them directly the way
            // CreateNewUser does. The `hashed` cast expects the plain value.
            $user = new User([
                'name' => $name,
                'email' => $email,
            ]);
            $user->password = $password;
            $user->email_verified_at = now();
            $user->save();

            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role) {
                $user->assignRole($role);
            } else {
                $this->warn("Role \"{$roleName}\" does not exist — the account was created without one.");
            }

            return $user;
        });

        $this->newLine();
        $this->info("Created user #{$user->id} ({$user->email}).");
        $this->line("  Name:     {$name}");
        $this->line("  Password: {$password}");
        $this->line("  Role:     {$roleName}");

        if (! $this->option('password')) {
            $this->newLine();
            $this->warn('That password was generated — copy it now, it will not be shown again.');
        }

        return self::SUCCESS;
    }
}
