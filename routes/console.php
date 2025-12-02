<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run daily backups every day at 2:00 AM
Schedule::command('backups:run daily')->dailyAt('02:00');

// Run weekly backups every Sunday at 3:00 AM
Schedule::command('backups:run weekly')->weeklyOn(0, '03:00');
