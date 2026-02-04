<?php

namespace App\ViewModels\BuyGood;

use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use App\Enums\Functions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PurchaseViewModel
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	private $_function 	= Functions::BG_PURCHASE;
	private $_backRoute	= '';
	private $_data = [];
	
	public function __construct()
	{
		#initialize
		$this->_data['action'] 		= NULL; #enum form action
		$this->_data['statistics']	= [];
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
	
	/* initialize
	 * @params: enum
	 * @params: string
	 * @params: string
	 * @return: void
	 */
	public function initialize($action)
	{
		#初始化各參數及Form Options
		$this->_data['action']	= $action;
		$this->fail(''); #Default
		
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
		data_set($this->_data, 'search.stDate', $searchStDate);
		data_set($this->_data, 'search.endDate', $searchEndDate);
	}
	
	/* Get initial data of search form 
	 * @params: date
	 * @params: date
	 * @return: void
	 */
	public function getSearchStDate()
	{
		return data_get($this->_data, 'search.stDate', '');
	}
	
	public function getSearchEndDate()
	{
		return data_get($this->_data, 'search.endDate', '');
	}
	
	public function isDataEmpty()
	{
		if (empty($this->_data['statistics']) OR (empty($this->_data['statistics']['area']) && empty($this->_data['statistics']['shop'])))
			return TRUE;
		else
			return FALSE;
	}
	
	public function hasExportData()
	{
		if ($this->isDataEmpty() OR empty($this->_data['statistics']['exportToken']))
			return FALSE;
		else
			return TRUE;
	}
	
	/* breadcrumb
	 * @params: 
	 * @return: array
	 */
	public function breadcrumb()
	{
		#Custom
		$breadcrumb 	= [];#wifi_1_bar
		$breadcrumb[] 	= '御廚．進銷存報表';
		$breadcrumb[]	= $this->_function->label(); 
		
		return $breadcrumb;
	}
}