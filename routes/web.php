<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SigninController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewReleaseController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PurchaseController;
use App\Http\Middleware\AuthPermission;

/* Login */
Route::get('/', [SigninController::class, 'showSignin'])->name('signin');
Route::get('signin', [SigninController::class, 'showSignin'])->name('signin');
Route::post('signin', [SigninController::class, 'signin'])->name('signin.post');
Route::get('signout', [SigninController::class, 'signout'])->name('signout');

Route::middleware([AuthPermission::class])->group(function(){
	/* Home */
	Route::get('home', [HomeController::class, 'index'])->name('home');
	
	/* 新品 */
	Route::get('new_releases/pork_ribs', [NewReleaseController::class, 'index']);
	Route::get('new_releases/tomato_beef', [NewReleaseController::class, 'index']);
	Route::get('new_releases/egg_tofu', [NewReleaseController::class, 'index']);
	#Route::get('new_releases/braised_pork', [NewReleaseController::class, 'index']);
	#Route::get('new_releases/braised_gravy', [NewReleaseController::class, 'index']);
	Route::get('new_releases/pork_gravy', [NewReleaseController::class, 'index']);
	Route::post('new_releases/{segment}/search', [NewReleaseController::class, 'search'])->name('new_releases.search');
	
	/* 進銷存報表 */
	Route::get('purchase/bg', [PurchaseController::class, 'showSearchBg']);
	Route::post('purchase/search', [PurchaseController::class, 'search'])->name('purchase.search');
	
	/* 身份管理 */
	Route::get('roles', [RoleController::class, 'list'])->name('role.list');
	Route::get('roles/list', [RoleController::class, 'list'])->name('role.list');
	Route::get('roles/create', [RoleController::class, 'showCreate'])->name('role.create');
	Route::post('roles/create', [RoleController::class, 'create'])->name('role.create.post');
	Route::get('roles/update/{id}', [RoleController::class, 'showUpdate'])->name('role.update');
	Route::post('roles/update', [RoleController::class, 'update'])->name('role.update.post');
	Route::post('roles/delete/{id}', [RoleController::class, 'delete'])->name('role.delete.post');

	/* 帳號管理 */
	Route::get('users', [UserController::class, 'list'])->name('user.list');
	Route::get('users/list', [UserController::class, 'list'])->name('user.list');
	Route::post('users/search', [UserController::class, 'search'])->name('user.search');
	Route::get('users/create', [UserController::class, 'showCreate'])->name('user.create');
	Route::post('users/create', [UserController::class, 'create'])->name('user.create.post');
	Route::get('users/update/{id}', [UserController::class, 'showUpdate'])->name('user.update');
	Route::post('users/update', [UserController::class, 'update'])->name('user.update.post');
	Route::post('users/delete/{id}', [UserController::class, 'delete'])->name('user.delete.post');
});


