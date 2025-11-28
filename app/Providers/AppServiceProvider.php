<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Facades\Event;
use PDO;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        #20251128 Tristan: DB Collection to Assoc Array
		Event::listen(StatementPrepared::class, function ($event) {
			$event->statement->setFetchMode(PDO::FETCH_ASSOC);
		});
    }
}
