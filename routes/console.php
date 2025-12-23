<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\UpdateNewReleaseDataToLocal;

#* * * * * cd /var/www/html/SalesDashboard && php artisan schedule:run >> /dev/null 2>&1

#Update data for current day
#橙汁排骨
Schedule::command('new-release:update-to-local porkRibs 1')->everyTwoMinutes()->between('10:00', '23:00');
#蕃茄牛三寶
Schedule::command('new-release:update-to-local tomatoBeef 1')->everyTwoMinutes()->between('10:00', '23:00');
#主廚秘製滷肉飯
Schedule::command('new-release:update-to-local braisedPork 1')->everyTwoMinutes()->between('10:00', '23:00');
#老皮嫩肉
Schedule::command('new-release:update-to-local eggTofu 1')->everyTwoMinutes()->between('10:00', '23:00');
#秘製滷肉汁
Schedule::command('new-release:update-to-local braisedGravy 1')->everyTwoMinutes()->between('10:00', '23:00');

#Update data for last 7 day
#橙汁排骨
Schedule::command('new-release:update-to-local porkRibs')->dailyAt('23:00'); 
#蕃茄牛三寶
Schedule::command('new-release:update-to-local tomatoBeef')->dailyAt('23:05');
#主廚秘製滷肉飯
Schedule::command('new-release:update-to-local braisedPork')->dailyAt('23:10');
#老皮嫩肉
Schedule::command('new-release:update-to-local eggTofu')->dailyAt('23:15');
#秘製滷肉汁
Schedule::command('new-release:update-to-local braisedGravy')->dailyAt('23:20');

/*
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
*/