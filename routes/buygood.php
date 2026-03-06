<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\NewReleaseController;

use App\Http\Controllers\BaFang\NewReleaseController as BfNewReleaseController;

use App\Http\Controllers\BuyGood\NewReleaseController as BgNewReleaseController;
use App\Http\Controllers\BuyGood\PurchaseController as BgPurchaseController;
use App\Http\Controllers\BuyGood\SalesController as BgSalesController;


use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\AccessPermissionMiddleware;
use App\Enums\Functions;

/***** 八方 *****/
Route::middleware([AuthMiddleware::class])->group(function(){
	
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BG_NEW_RELEASE->value, ':')])->group(function(){
		Route::get('new_releases', [NewReleaseController::class, 'showSearch'])->name('new_releases');
		Route::post('new_releases/search', [NewReleaseController::class, 'search'])->name('new_releases.search');
	});
	
	/***** 梁社漢 *****/
	Route::namespace('App\Http\Controllers\BuyGood')->prefix('bg')->group(function () {
		/* 新品 */
		Route::get('new_releases/pork_ribs', [BgNewReleaseController::class, 'porkRibs'])->name('new_releases');
		Route::get('new_releases/tomato_beef', [BgNewReleaseController::class, 'tomatoBeef']);
		Route::get('new_releases/egg_tofu', [BgNewReleaseController::class, 'eggTofu']);
		Route::get('new_releases/pork_gravy', [BgNewReleaseController::class, 'porkGravy']);
		Route::get('new_releases/beef_short_ribs', [BgNewReleaseController::class, 'beefShortRibs']);
		Route::post('new_releases/{segment}/search', [BgNewReleaseController::class, 'search'])->name('bg.new_releases.search');
		#Route::get('new_releases/braised_pork', [BgNewReleaseController::class, 'index']);
		#Route::get('new_releases/braised_gravy', [BgNewReleaseController::class, 'index']);
		
		/* 出貨報表 */
		Route::get('purchase', [BgPurchaseController::class, 'showSearch'])->name('purchase');
		Route::post('purchase/search', [BgPurchaseController::class, 'search'])->name('bg.purchase.search');
		Route::get('purchase/export/{token}', [BgPurchaseController::class, 'export'])->name('buygood.purchase.export');
		
		/* 銷售報表 */
		Route::get('sales', [BgSalesController::class, 'showSearch'])->name('sales');
		Route::post('sales/search', [BgSalesController::class, 'search'])->name('buygood.sales.search');
		Route::get('sales/export/{token}', [BgSalesController::class, 'export'])->name('bg.sales.export');
	});
	
	
});


