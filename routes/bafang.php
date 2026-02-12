<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BaFang\NewReleaseController as BfNewReleaseController;


Route::get('/new_releases/beef_short_ribs', [BfNewReleaseController::class, 'beefShortRibs']);
Route::post('/new_releases/{segment}/search', [BfNewReleaseController::class, 'search'])->name('new_releases.search');
