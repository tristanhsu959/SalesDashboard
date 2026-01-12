<?php

namespace App\ViewModels;

use App\Enums\Brand;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PurchaseViewModel
{
	private $_title = '進銷存報表';
	private $_data = [];
	
	public function __construct()
	{
		#initialize
		$this->_data['brand']		= '';
		$this->_data['action'] 		= NULL; #enum form action
		$this->_data['status']		= NULL;
		$this->_data['msg'] 		= '';
		$this->_data['statistics']	= [];
		$this->_data['search']		= [];
	}
	
	public function __set($name, $value)
    {
		$this->_data[$name] = $value;
    }
	
	public function __get($name)
    {
		return data_get($this->_data, $name, '');
	}
	
	/* 須有isset, 否則empty()會判別錯誤 */
	public function __isset($name)
    {
		return array_key_exists($name, $this->_data);
	}
	
	/* Status / Msg
	 * @params: 
	 * @return: boolean
	 */
	public function success($msg = NULL)
	{
		$this->_data['status'] 	= TRUE;
		$this->_data['msg'] 	= $msg ?? '';
	}
	
	public function fail($msg)
	{
		$this->_data['status'] 	= FALSE;
		$this->_data['msg'] 	= $msg;
	}
	
	/* initialize
	 * @params: enum
	 * @params: string
	 * @params: string
	 * @return: void
	 */
	public function initialize($action, $brand = NULL)
	{
		#初始化各參數及Form Options
		$this->_data['action']	= $action;
		$this->success();
		
		#default today
		$today = now()->format('Y-m-d');
		$this->keepSearchData($brand, $today, $today);
		
		$this->_setOptions();
	}
	
	/* Search Form參數選項
	 * @params: 
	 * @return: void
	 */
	private function _setOptions()
	{
		$brands 	= [];
		$brands[] 	= Brand::BUYGOOD; #目前只有梁社漢
		data_set($this->_data, 'option.brandList', $brands);
	}
	
	/* Keep form search data
	 * @params: int
	 * @params: date
	 * @params: date
	 * @return: void
	 */
	public function keepSearchData($searchBrand, $searchStDate, $searchEndDate)
    {
		data_set($this->_data, 'search.brand', $searchBrand);
		data_set($this->_data, 'search.stDate', $searchStDate);
		data_set($this->_data, 'search.endDate', $searchEndDate);
	}
	
	/* Get initial data of search form 
	 * @params: date
	 * @params: date
	 * @return: void
	 */
	public function getOptionBrandList()
	{
		return data_get($this->_data, 'option.brandList', []);
	}
	
	public function getSearchBrand()
	{
		return data_get($this->_data, 'search.brand', 0);
	}
	
	public function getSearchStDate()
	{
		return data_get($this->_data, 'search.stDate', '');
	}
	
	public function getSearchEndDate()
	{
		return data_get($this->_data, 'search.endDate', '');
	}
	
	/* 取時間序
	 * @params: boolean #default desc
	 * @return: array
	 */
	// public function getDateRange($orderAsc = FALSE)
    // {
		// $order = $orderAsc ? 'ASC' : 'DESC';
		// $st = Carbon::create($this->_data['statistics']['startDate']);
		// $end = Carbon::create($this->_data['statistics']['endDate']);
		// $period = CarbonPeriod::create($st, $end);

		// $dateList = [];

		// foreach ($period as $date) 
		// {
			// $dateList[] = $date->format('Y-m-d');
		// }
		
		// if ($order == 'DESC')
			// $dateList = Arr::sortDesc($dateList);
		
		// return $dateList;
	// }
	
	/* Form Style */
	public function getBreadcrumb()
    {
		return $this->_title . ' | ' . $this->action->label();
	}
	
	// public function getSaleDate()
    // {
		// return data_get($this->_data, 'config.saleDate', '');
	// }
	
	// public function getSaleEndDate()
    // {
		// return data_get($this->_data, 'config.saleEndDate', '');
	// }
	
	
}