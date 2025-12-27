<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use PDO;
use App\Traits\AuthorizationTrait;
use App\Traits\MenuTrait;
use App\ViewModels\MenuViewModel;

class AppServiceProvider extends ServiceProvider
{
	use AuthorizationTrait, MenuTrait;
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
			
			if (in_array($view->getName(), ['signin', 'signout']) == FALSE)
			{				
				$currentUser= $this->getCurrentUser();
				
				if ($currentUser) 
				{
					$authMenu	= $this->getAuthorizedMenu($currentUser);
					$appMenu 	= new MenuViewModel($authMenu);
					$view->with('currentUser', $currentUser)->with('appMenu', $appMenu);
				}
			}
		});
    }
}
