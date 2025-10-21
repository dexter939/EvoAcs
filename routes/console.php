<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('metrics:collect')->everyFiveMinutes()->withoutOverlapping();

Schedule::command('horizon:snapshot')->everyFiveMinutes();

Schedule::command('system:check-updates --auto-stage')
    ->weekly()
    ->mondays()
    ->at('03:00')
    ->withoutOverlapping()
    ->onOneServer();
