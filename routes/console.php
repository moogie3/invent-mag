<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Reset the demo tenant every night at midnight
Schedule::command('tenant:reset-demo')->dailyAt('00:00');

// Process scheduled plan downgrades daily at 1:00 AM
Schedule::command('billing:process-scheduled-changes')->dailyAt('01:00');

// Check and expire subscriptions whose billing period has passed, daily at 1:30 AM
Schedule::command('billing:check-expired')->dailyAt('01:30');