<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 06 P2: shift yang masih terbuka ditutup paksa jam 22:00 (jam tutup outlet).
Schedule::command('shifts:auto-close')->dailyAt('22:00')->withoutOverlapping();
