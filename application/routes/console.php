<?php

use App\Console\Commands\Samurai;
use App\Console\Commands\Trump;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(Samurai::class)->everyThreeHours();
Schedule::command(Trump::class)->dailyAt('12:00');