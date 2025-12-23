<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

#* * * * * cd /var/www/html/SalesDashboard && php artisan schedule:run >> /dev/null 2>&1
#橙汁排骨
Schedule::command('new-release:update-to-local porkRibs')->twiceDailyAt(13, 20, 30)->withoutOverlapping(); #13:30/20:30
#蕃茄牛三寶
Schedule::command('new-release:update-to-local tomatoBeef')->twiceDailyAt(13, 20, 30)->withoutOverlapping();
#主廚秘製滷肉飯
Schedule::command('new-release:update-to-local braisedPork')->twiceDailyAt(13, 20, 30)->withoutOverlapping();
#老皮嫩肉
Schedule::command('new-release:update-to-local eggTofu')->twiceDailyAt(13, 20, 30)->withoutOverlapping();
#秘製滷肉汁
Schedule::command('new-release:update-to-local braisedGravy')->twiceDailyAt(13, 20, 30)->withoutOverlapping();

/*
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
*/