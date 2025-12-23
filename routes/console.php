<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\UpdateNewReleaseDataToLocal;

#* * * * * cd /var/www/html/SalesDashboard && php artisan schedule:run >> /dev/null 2>&1

#Update data for current day
#橙汁排骨
Schedule::command('new-release:update-to-local porkRibs 1')->everyFifteenMinutes()->between('10:00', '21:00')->withoutOverlapping();
#蕃茄牛三寶
Schedule::command('new-release:update-to-local tomatoBeef 1')->everyFifteenMinutes()->between('10:00', '21:00')->withoutOverlapping();
#主廚秘製滷肉飯
Schedule::command('new-release:update-to-local braisedPork 1')->everyFifteenMinutes()->between('10:00', '21:00')->withoutOverlapping();
#老皮嫩肉
Schedule::command('new-release:update-to-local eggTofu 1')->everyFifteenMinutes()->between('10:00', '21:00')->withoutOverlapping();
#秘製滷肉汁
Schedule::command('new-release:update-to-local braisedGravy 1')->everyFifteenMinutes()->between('10:00', '21:00')->withoutOverlapping();

#Update data for last 7 day
#橙汁排骨
Schedule::command('new-release:update-to-local porkRibs')->dailyAt('23:00')->withoutOverlapping(); 
#蕃茄牛三寶
Schedule::command('new-release:update-to-local tomatoBeef')->dailyAt('23:05')->withoutOverlapping();
#主廚秘製滷肉飯
Schedule::command('new-release:update-to-local braisedPork')->dailyAt('23:10')->withoutOverlapping();
#老皮嫩肉
Schedule::command('new-release:update-to-local eggTofu')->dailyAt('23:15')->withoutOverlapping();
#秘製滷肉汁
Schedule::command('new-release:update-to-local braisedGravy')->dailyAt('23:20')->withoutOverlapping();

/*
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
*/