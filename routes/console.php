<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

#* * * * * cd /var/www/html/SalesDashboard && php artisan schedule:run >> /dev/null 2>&1
#橙汁排骨
Schedule::command('new-release:update-to-local porkRibs')->everyTenMinutes()->withoutOverlapping();
#蕃茄牛三寶
Schedule::command('new-release:update-to-local tomatoBeef')->everyTenMinutes()->withoutOverlapping();
#主廚秘製滷肉飯
Schedule::command('new-release:update-to-local braisedPork')->everyTenMinutes()->withoutOverlapping();
#老皮嫩肉
Schedule::command('new-release:update-to-local eggTofu')->everyTenMinutes()->withoutOverlapping();
#秘製滷肉汁
Schedule::command('new-release:update-to-local braisedGravy')->everyTenMinutes()->withoutOverlapping();

/*
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
*/