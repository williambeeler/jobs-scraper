<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:run --only-db --disable-notifications')
    ->dailyAt('10:00')
    ->timezone((string) config('app.display_timezone', 'America/Chicago'))
    ->withoutOverlapping();

Schedule::command('news:refresh-feeds')
    ->everyThreeHours()
    ->timezone((string) config('app.display_timezone', 'America/Chicago'))
    ->withoutOverlapping(180);
