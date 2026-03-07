<?php

namespace App\ViewModels;

use App\Services\NewReleaseService;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use App\Enums\FormAction;
use App\Enums\Functions;
use App\Enums\Brand;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Fluent;

class NewReleaseViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct(protected NewReleaseService $_service)
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
	public function initialize($brand , $function)
	{
		$this->brand	= $brand;
		$this->function = $function;
		$this->statistics = [];
		
		$this->_setOptions();
	}
	
	/* Form所屬的參數選項
	 * @params:  
	 * @return: void
	 */
	private function _setOptions()
	{
		$this->set('options.newItems', $this->_service->getNewItemOptions($this->brand->value));
	}
	
	/* Form submit action
	 * @params: 
	 * @return: string
	 */
	public function getFormAction() : string
    {
		return match($this->brand)
		{
			Brand::BAFANG	=> route(Str::replace('?', Brand::BAFANG->code(), '?.new_releases.search')),
			Brand::BUYGOOD	=> route(Str::replace('?', Brand::BUYGOOD->code(), '?.new_releases.search')),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: string
	 */
	public function keepSearchData($searchNewItemId = 0, $searchStDate = '', $searchEndDate = '')
    {
		$this->set('search.newItemId', $searchNewItemId);
		$this->set('search.stDate', $searchStDate);
		$this->set('search.endDate', $searchEndDate);
		$this->set('search.today', Carbon::now()->format('Y-m-d'));
	}
	
	public function isDataEmpty()
	{
		if (empty(Arr::collapse($this->statistics)))
			return TRUE;
		else
			return FALSE;
	}
	
	/* 取時間序
	 * @params: boolean #default desc
	 * @return: array
	 */
	public function getDateRange($orderAsc = FALSE)
    {
		$order = $orderAsc ? 'ASC' : 'DESC';
		$st = Carbon::create($this->_data['statistics']['startDate']);
		$end = Carbon::create($this->_data['statistics']['endDate']);
		$period = CarbonPeriod::create($st, $end);

		$dateList = [];

		foreach ($period as $date) 
		{
			$dateList[] = $date->format('Y-m-d');
		}
		
		if ($order == 'DESC')
			$dateList = Arr::sortDesc($dateList);
		
		return $dateList;
	}
	
	/* 時間Header, 顯示方式不同
	 * @params: boolean #default desc
	 * @return: array
	 */
	public function renderDateHeader($orderAsc = FALSE)
	{
		$dateList = $this->getDateRange($orderAsc);
		#不重複Year顯示處理
		$year = '';
		$header = [];
		
		foreach ($dateList as $date) 
		{
			$thisYear 	= Str::before($date, '-');
			$header[]	= Str::replaceFirst($year, '', $date); #-01-11, no year
			$year = $thisYear;
		}
		
		return $header;
	}
}