<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\NewReleaseController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\DailyRevenueController;
use App\Http\Controllers\ShipmentsController;
use App\Http\Controllers\MonthlyFillingController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\PurchaseSalesController;
use App\Http\Controllers\PurchaseReportController;
use App\Http\Controllers\EzOrderPosController;

use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\AccessPermissionMiddleware;
use App\Enums\Functions;

/***** 八方 *****/
Route::middleware([AuthMiddleware::class])->group(function(){
	
	#name不用加prefix
	/* 新品 */
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BF_NEW_RELEASE->value, ':')])->group(function(){
		Route::get('new_releases', [NewReleaseController::class, 'showSearch'])->name('new_releases');
		Route::post('new_releases/search', [NewReleaseController::class, 'search'])->name('new_releases.search');
		Route::get('new_releases/export/{token}', [NewReleaseController::class, 'export'])->name('new_releases.export');
	});
	
	/* 銷售報表 */
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BF_SALES->value, ':')])->group(function(){
		Route::get('sales', [SalesController::class, 'showSearch'])->name('sales');
		Route::post('sales/search', [SalesController::class, 'search'])->name('sales.search');
		Route::get('sales/export/{token}', [SalesController::class, 'export'])->name('sales.export');
	});	
	
	/* 八方點 */
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BF_EZORDER_POS->value, ':')])->group(function(){
		Route::get('ezorder_pos', [EzOrderPosController::class, 'showSearch'])->name('ezorder_pos');
		Route::post('ezorder_pos/search', [EzOrderPosController::class, 'search'])->name('ezorder_pos.search');
		Route::get('ezorder_pos/export/{token}', [EzOrderPosController::class, 'export'])->name('ezorder_pos.export');
	});
	
	/* 出貨總量查詢 */
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BF_SHIPMENTS->value, ':')])->group(function(){
		Route::get('shipments', [ShipmentsController::class, 'showSearch'])->name('shipments');
		Route::post('shipments/search', [ShipmentsController::class, 'search'])->name('shipments.search');
		Route::get('shipments/export/{token}', [ShipmentsController::class, 'export'])->name('shipments.export');
	});	
	
	/* 出貨報表 */
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BF_PURCHASE_REPORT->value, ':')])->group(function(){
		Route::get('purchase_report', [PurchaseReportController::class, 'showSearch'])->name('purchase_report');
		Route::post('purchase_report/search', [PurchaseReportController::class, 'search'])->name('purchase_report.search');
		Route::get('purchase_report/export/{token}', [PurchaseReportController::class, 'export'])->name('purchase_report.export');
	});	
	
	/* 月初報表 */
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BF_MONTHLY_FILLING->value, ':')])->group(function(){
		Route::get('monthly_filling', [MonthlyFillingController::class, 'showSearch'])->name('monthly_filling');
		Route::post('monthly_filling/search', [MonthlyFillingController::class, 'search'])->name('monthly_filling.search');
		Route::get('monthly_filling/export/{token}', [MonthlyFillingController::class, 'export'])->name('monthly_filling.export');
	});	
	
	/* 本日營收 */
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BF_DAILY_REVENUE->value, ':')])->group(function(){
		Route::get('daily_revenue', [DailyRevenueController::class, 'showSearch'])->name('daily_revenue');
		Route::post('daily_revenue/search', [DailyRevenueController::class, 'search'])->name('daily_revenue.search');
		Route::get('daily_revenue/export/{token}', [DailyRevenueController::class, 'export'])->name('daily_revenue.export');
	});
	
	/* 門店資訊 */
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BF_MERCHANT->value, ':')])->group(function(){
		Route::get('merchant', [MerchantController::class, 'showSearch'])->name('merchant');
		Route::post('merchant/search', [MerchantController::class, 'search'])->name('merchant.search');
		Route::get('merchant/export/{token}', [MerchantController::class, 'export'])->name('merchant.export');
	});	
	
	/* 門店進貨及銷售 */
	Route::middleware([AccessPermissionMiddleware::class . Str::start(Functions::BF_PURCHASE_SALES->value, ':')])->group(function(){
		Route::get('purchase_sales', [PurchaseSalesController::class, 'showSearch'])->name('purchase_sales');
		Route::post('purchase_sales/search', [PurchaseSalesController::class, 'search'])->name('purchase_sales.search');
		Route::get('purchase_sales/search', [PurchaseSalesController::class, 'search'])->name('purchase_sales.list');
		Route::post('purchase_sales/detail', [PurchaseSalesController::class, 'detail'])->name('purchase_sales.detail');
		Route::get('purchase_sales/export/{token}', [PurchaseSalesController::class, 'export'])->name('purchase_sales.export');
	});
});
