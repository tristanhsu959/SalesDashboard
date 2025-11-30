<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use PDO;
use App\Traits\AuthorizationTrait;
use App\Traits\RolePermissionTrait;
use App\ViewModels\MenuViewModel;

class AppServiceProvider extends ServiceProvider
{
	use AuthorizationTrait, RolePermissionTrait;
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
		
		#View share not work, because session is not available
		View::composer('*', function ($view) {
			$signinInfo = $this->getSigninUserInfo();
			$appMenu = new MenuViewModel($this->getMenuByPermission());
			
			if ($signinInfo) 
				$view->with('signinInfo', $signinInfo)->with('appMenu', $appMenu);
		});

    }
}
