<?php

namespace App\ViewHelpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RoleHelper
{
    public function getHeaderByShop($startDate, $endDate)
    {
		$before	= ['h1'=>'區域', 'h2'=>'門店代號', 'h3'=>'門店名稱'];
		$last 	= ['f1'=>'銷售總量', 'f2'=>'平均銷售數量'];
		$dateList = $this->getDateRangeList($startDate, $endDate);
		
		return array_merge($before, $dateList, $last);
    }
	
	public function getDateRangeList($startDate, $endDate, $order = 'DESC')
    {
		$st = Carbon::create($startDate);
		$end = Carbon::create($endDate);
		$period = CarbonPeriod::create($st, $end);

		$dateList = [];

		foreach ($period as $date) 
		{
			$dateList[] = $date->toDateString();
		}
		
		if ($order == 'DESC')
			$dateList = Arr::sortDesc($dateList);
		
		return $dateList;
	}
}