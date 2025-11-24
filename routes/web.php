<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewReleaseController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;


/* Login */
Route::get('/', [AuthController::class, 'signin'])->name('signin');
Route::get('signin', [AuthController::class, 'signin'])->name('signin');
Route::post('auth', [AuthController::class, 'authSignin'])->name('auth');
Route::get('signout', [AuthController::class, 'signout'])->name('signout');

/* Home */
Route::get('home', [HomeController::class, 'index'])->name('home');

/* 新品 */
Route::get('new_releases/pork_ribs', [NewReleaseController::class, 'getPorkRibsStatistics']);
Route::get('new_releases/tomato_beef', [NewReleaseController::class, 'getTomatoBeefStatistics']);

/* 身份管理 */
Route::get('roles', [RoleController::class, 'list']);
Route::get('roles/list', [RoleController::class, 'list']);
Route::get('roles/create', [RoleController::class, 'showCreate']);
Route::post('roles/create', [RoleController::class, 'create']);
Route::get('roles/update/{id?}', [RoleController::class, 'showUpdate']);
Route::post('roles/update/{id?}', [RoleController::class, 'update']);
Route::post('roles/delete/{id?}', [RoleController::class, 'remove']);

/* 帳號管理 */
Route::get('users', [UserController::class, 'list']);
Route::get('users/list', [UserController::class, 'list']);
Route::get('users/create', [UserController::class, 'create']);
Route::get('users/update/{id?}', [UserController::class, 'update']);
Route::get('users/delete/{id?}', [UserController::class, 'remove']);