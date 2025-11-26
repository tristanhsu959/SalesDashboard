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
	
	protected function connectSaleDashboard($table = NULL)
	{
		if (empty($table))
			return DB::connection('SaleDashboard');
		else
			return DB::connection('SaleDashboard')->table($table)->lock('WITH(NOLOCK)');
	}
}
