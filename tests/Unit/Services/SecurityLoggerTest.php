<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\SecurityLogger;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class SecurityLoggerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_log_password_change()
    {
        $user = User::factory()->create();

        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf()
            ->once();

        Log::shouldReceive('info')
            ->once()
            ->andReturnNull();

        SecurityLogger::logPasswordChange($user);
    }

    public function test_log_failed_login()
    {
        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf()
            ->once();

        Log::shouldReceive('warning')
            ->once()
            ->andReturnNull();

        SecurityLogger::logFailedLogin('test@example.com');
    }

    public function test_log_failed_login_with_reason()
    {
        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf()
            ->once();

        Log::shouldReceive('warning')
            ->once()
            ->andReturnNull();

        SecurityLogger::logFailedLogin('test@example.com', 'Account locked');
    }

    public function test_log_successful_login()
    {
        $user = User::factory()->create();

        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf()
            ->once();

        Log::shouldReceive('info')
            ->once()
            ->andReturnNull();

        SecurityLogger::logSuccessfulLogin($user);
    }

    public function test_log_permission_denied()
    {
        $user = User::factory()->create();

        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf()
            ->once();

        Log::shouldReceive('warning')
            ->once()
            ->andReturnNull();

        SecurityLogger::logPermissionDenied($user, 'create-product');
    }

    public function test_log_suspicious_activity()
    {
        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf()
            ->once();

        Log::shouldReceive('alert')
            ->once()
            ->andReturnNull();

        SecurityLogger::logSuspiciousActivity('Multiple failed login attempts');
    }

    public function test_log_suspicious_activity_with_data()
    {
        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf()
            ->once();

        Log::shouldReceive('alert')
            ->once()
            ->andReturnNull();

        SecurityLogger::logSuspiciousActivity('Unusual IP detected', [
            'ip' => '192.168.1.100',
            'attempts' => 5,
        ]);
    }

    public function test_it_falls_back_to_the_main_log_when_the_security_channel_fails()
    {
        // Mirrors the production failure: the web user cannot append to the
        // security log, so opening it throws.
        Log::shouldReceive('channel')
            ->with('security')
            ->andThrow(new \UnexpectedValueException('Permission denied'))
            ->once();

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_starts_with($message, 'SECURITY:')
                    && isset($context['email']);
            })
            ->andReturnNull();

        SecurityLogger::logFailedLogin('test@example.com');
    }

    public function test_it_never_propagates_a_logging_failure_to_the_caller()
    {
        // Both the security channel and the main log are unavailable.
        Log::shouldReceive('channel')
            ->andThrow(new \UnexpectedValueException('Permission denied'));

        Log::shouldReceive('error')
            ->andThrow(new \UnexpectedValueException('Permission denied'));

        $threw = false;

        try {
            SecurityLogger::logPasswordChange(User::factory()->create());
            SecurityLogger::log('info', 'Backup downloaded', ['tenant_id' => 1]);
        } catch (\Throwable $e) {
            $threw = true;
        }

        $this->assertFalse(
            $threw,
            'A security audit write must never break the request that triggered it'
        );
    }
}
