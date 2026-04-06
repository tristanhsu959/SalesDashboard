<?php

namespace App\ViewModels\BaFang;

use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class NewReleaseViewModel
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	private $_function 	= NULL;
	private $_backRoute	= '';
	private $_data = [];
	
	public function __construct()
	{
		#initialize
		$this->_data['action'] 		= NULL; #enum form action
		$this->_data['statistics']	= [];
		$this->_data['configKey']	= '';
		$this->_data['config']		= [];
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
	
	public function getFunctionKey()
	{
		return $this->_function->value; #view無法存enum
	}
	
	/* initialize
	 * @params: enum
	 * @params: string
	 * @params: string
	 * @return: void
	 */
	public function initialize($action , $configKey, $functionKey)
	{
		$this->_function 			= $functionKey;
		#初始化各參數及Form Options
		$this->_data['action']		= $action;
		$this->fail(''); #因共用頁面, 故default false判別顯示用
		$this->_data['configKey'] 	= $configKey;
		
		if (! empty($configKey))
			$this->_data['config'] 	= config("bafang.new_release.products.{$configKey}");
		
		$this->_setOptions();
	}
	
	/* Search Form參數選項
	 * @params: 
	 * @return: void
	 */
	private function _setOptions()
	{
		if (empty($configKey))
		{
			data_set($this->_data, 'search.stMin', '');
			data_set($this->_data, 'search.endMax', '');
		}
		else
		{
			data_set($this->_data, 'search.stMin', $this->_data['config']['saleDate']);
			data_set($this->_data, 'search.endMax', $this->_data['config']['saleEndDate']);
		}
		
		#Default search date
		data_set($this->_data, 'search.stDate', $this->getDefaultSearchStDate());
		data_set($this->_data, 'search.endDate', $this->getDefaultSearchEndDate());
	}
	
	/* Keep form search data
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
	
	public function getDefaultSearchStDate()
    {
		#一定會有
		return data_get($this->_data, 'config.saleDate', '');
	}
	
	public function getDefaultSearchEndDate()
    {
		return data_get($this->_data, 'config.saleEndDate', NULL) ?? Carbon::now()->format('Y-m-d'); #date picker必須為Y-m-d才能正常顯示
	}
	
	public function getSegment()
    {
		return Str::snake(data_get($this->_data, 'configKey'), '_');
	}
	
	public function isDataEmpty()
	{
		if (empty($this->_data['statistics']) OR (empty($this->_data['statistics']['area']) && empty($this->_data['statistics']['shop']) 
			&& empty($this->_data['statistics']['top']) && empty($this->_data['statistics']['last'])))
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
	
	/* Form Style */
	public function getSaleDate()
    {
		return data_get($this->_data, 'config.saleDate', '');
	}
	
	public function getSaleEndDate()
    {
		return data_get($this->_data, 'config.saleEndDate', '');
	}
	
	/* breadcrumb
	 * @params: 
	 * @return: array
	 */
	public function breadcrumb()
	{
		#Custom
		$breadcrumb 	= [];#wifi_1_bar
		$breadcrumb[] 	= '八方．新品銷售';
		$breadcrumb[]	= $this->_function->label(); 
		
		return $breadcrumb;
	}
	
}