<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BuyGood\NewReleaseController as BgNewReleaseController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PurchaseController;
use App\Http\Middleware\AccessPermissionMiddleware;

/* Login */
Route::get('/', [AuthController::class, 'showSignin'])->name('signin');
Route::get('signin', [AuthController::class, 'showSignin'])->name('signin');
Route::post('signin', [AuthController::class, 'signin'])->name('signin.post');
Route::get('signout', [AuthController::class, 'signout'])->name('signout');

Route::middleware([AccessPermissionMiddleware::class])->group(function(){
	/* Home */
	Route::get('home', [HomeController::class, 'index'])->name('home');
	
	/* 八方 */
	Route::namespace('App\Http\Controllers\BaFang')->prefix('bf')->group(function () {
	});
	
	/* 梁社漢 */
	Route::namespace('App\Http\Controllers\BuyGood')->prefix('bg')->group(function () {
		/* 新品 */
		Route::get('new_releases/pork_ribs', [BgNewReleaseController::class, 'porkRibs']);
		Route::get('new_releases/tomato_beef', [BgNewReleaseController::class, 'tomatoBeef']);
		Route::get('new_releases/egg_tofu', [BgNewReleaseController::class, 'eggTofu']);
		Route::get('new_releases/pork_gravy', [BgNewReleaseController::class, 'porkGravy']);
		Route::post('new_releases/{segment}/search', [BgNewReleaseController::class, 'search'])->name('bg.new_releases.search');
		#Route::get('new_releases/braised_pork', [BgNewReleaseController::class, 'index']);
		#Route::get('new_releases/braised_gravy', [BgNewReleaseController::class, 'index']);
	});
	
	/* 進銷存報表 */
	Route::get('bg/purchase', [PurchaseController::class, 'showSearch']);
	Route::post('bg/purchase/search', [PurchaseController::class, 'search'])->name('purchase.search');
	Route::get('bg/sales', [SalesController::class, 'showSales']);
	Route::post('bg/sales/search', [SalesController::class, 'search'])->name('sales.search');
	
	/* 身份管理 */
	Route::get('role', [RoleController::class, 'list'])->name('role.list');
	Route::get('role/list', [RoleController::class, 'list']);
	Route::get('role/create', [RoleController::class, 'showCreate'])->name('role.create');
	Route::post('role/create', [RoleController::class, 'create'])->name('role.create.post');
	Route::get('role/update/{id}', [RoleController::class, 'showUpdate'])->name('role.update');
	Route::post('role/update', [RoleController::class, 'update'])->name('role.update.post');
	Route::post('role/delete/{id}', [RoleController::class, 'delete'])->name('role.delete.post');

	/* 帳號管理 */
	Route::get('user', [UserController::class, 'list'])->name('user.list');
	Route::get('user/list', [UserController::class, 'list']);
	Route::post('user/search', [UserController::class, 'search'])->name('user.search');
	Route::get('user/create', [UserController::class, 'showCreate'])->name('user.create');
	Route::post('user/create', [UserController::class, 'create'])->name('user.create.post');
	Route::get('user/update/{id}', [UserController::class, 'showUpdate'])->name('user.update');
	Route::post('user/update', [UserController::class, 'update'])->name('user.update.post');
	Route::post('user/delete/{id}', [UserController::class, 'delete'])->name('user.delete.post');
});


