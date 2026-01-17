<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class Repository
{
	protected function connectBFPosErp($table)
	{
		#八方
		return DB::connection('BFPosErp')->table($table)->lock('WITH(NOLOCK)');
	}
	
	protected function connectBGPosErp($table)
	{
		#梁社漢
		return DB::connection('BGPosErp')->table($table)->lock('WITH(NOLOCK)');
	}
	
	/* Local Sale[s]_Dashboard */
	protected function connectSalesDashboard($table = NULL)
	{
		if (empty($table))
			return DB::connection('SalesDashboard');
		else
			return DB::connection('SalesDashboard')->table($table); #無法用nolock
	}
	
	#屯山(北區)
	protected function connectOrderTS($table = NULL)
	{
		if (empty($table))
			return DB::connection('OrderTS');
		else
			return DB::connection('OrderTS')->table($table)->lock('WITH(NOLOCK)'); 
	}
	
	#二崙(南區)
	protected function connectOrderRL($table = NULL)
	{
		if (empty($table))
			return DB::connection('OrderRL');
		else
			return DB::connection('OrderRL')->table($table)->lock('WITH(NOLOCK)'); 
	}
	
	#norder database(新訂貨系統)
	protected function connectNewOrder($table = NULL)
	{
		if (empty($table))
			return DB::connection('NewOrder');
		else
			return DB::connection('NewOrder')->table($table)->lock('WITH(NOLOCK)'); 
	}
	
	/* 原測試機已改為Local MySql */
	/*protected function connectSaleDashboard($table = NULL)
	{
		return $this->connectLocalSalesDashboard($table);
		
		#deprecated
		if (empty($table))
			return DB::connection('SaleDashboard');
		else
			return DB::connection('SaleDashboard')->table($table)->lock('WITH(NOLOCK)');
		
	}*/
}
