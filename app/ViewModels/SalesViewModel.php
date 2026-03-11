<?php

namespace App\ViewModels;

use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\FormAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Fluent;

class SalesViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct()
	{
		$this->function		= NULL;
		$this->action 		= FormAction::LIST; 
		$this->backRoute 	= '';
		$this->success();
	}
	
	/* initialize
	 * @params: enum
	 * @params: string
	 * @params: string
	 * @return: void
	 */
	public function initialize($brand , $function, $action)
	{
		$this->brand	= $brand;
		$this->function = $function;
		$this->action	= $action;
		$this->statistics = [];
		
		#default today
		$today = now()->format('Y-m-d');
		$this->keepSearchData($today, $today);
	}
	
	/* Keep form search data
	 * @params: int
	 * @params: date
	 * @params: date
	 * @return: void
	 */
	public function keepSearchData($searchStDate, $searchEndDate)
    {
		$this->set('search.stDate', $searchStDate); 
		$this->set('search.endDate', $searchEndDate);
		$this->set('search.today', Carbon::now()->format('Y-m-d'));
	}
	
	/* Form submit action
	 * @params: 
	 * @return: string
	 */
	public function getFormAction() : string
    {
		return match($this->brand)
		{
			Brand::BAFANG	=> route(Str::replace('?', Brand::BAFANG->code(), '?.sales.search')),
			Brand::BUYGOOD	=> route(Str::replace('?', Brand::BUYGOOD->code(), '?.sales.search')),
		};
	}
	
	public function isDataEmpty()
	{
		if (empty(Arr::collapse($this->statistics)))
			return TRUE;
		else
			return FALSE;
	}
	
	public function hasExportData()
	{
		if ($this->isDataEmpty() OR empty($this->statistics['exportToken']))
			return FALSE;
		else
			return TRUE;
	}

}