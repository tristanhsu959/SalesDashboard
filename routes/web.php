<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewReleaseController;
use App\Http\Controllers\RoleController;


/* Login */
Route::get('/', [AuthController::class, 'index'])->name('index');
Route::get('login', [AuthController::class, 'index'])->name('login');
Route::post('auth', [AuthController::class, 'authLogin'])->name('auth');

/* Home */
Route::get('home', [HomeController::class, 'index'])->name('home');

/* 新品 */
Route::get('new_releases/pork_ribs', [NewReleaseController::class, 'getPorkRibsStatistics']);
Route::get('new_releases/tomato_beef', [NewReleaseController::class, 'getTomatoBeefStatistics']);

/* 身份管理 */
Route::get('roles', [RoleController::class, 'list']);
Route::get('roles/list', [RoleController::class, 'list']);
Route::get('roles/create', [RoleController::class, 'create']);
Route::get('roles/update', [RoleController::class, 'update']);
Route::get('roles/remove', [RoleController::class, 'remove']);