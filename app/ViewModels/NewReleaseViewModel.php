<?php

namespace App\ViewModels;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class NewReleaseViewModel
{
	private $_data = [];
	
	public function __construct()
	{
		#initialize
		$this->_data['action'] 		= NULL; #enum form action
		$this->_data['status']		= NULL;
		$this->_data['msg'] 		= '';
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
	 * @params: int
	 * @return: void
	 */
	public function initialize($action , $configKey = '')
	{
		#初始化各參數及Form Options
		$this->_data['action']		= $action;
		$this->_data['msg'] 		= '';
		$this->_data['configKey'] 	= $configKey;
		
		if (! empty($configKey))
			$this->_data['config'] 	= config("web.newrelease.products.{$configKey}");
		
		$this->_setOptions();
	}
	
	/* Search Form參數選項
	 * @params: 
	 * @return: void
	 */
	private function _setOptions()
	{
		$configKey = $this->_data['configKey'];
		
		if (empty($configKey))
		{
			data_set($this->_data, 'search.stMin', '');
			data_set($this->_data, 'search.endMax', '');
		}
		else
		{
			$config = config("web.newrelease.products.{$configKey}");
			
			data_set($this->_data, 'search.stMin', $config['saleDate']);
			data_set($this->_data, 'search.endMax', $config['saleEndDate']);
		}
	}
	
	/* Keep user search data
	 * @params: 
	 * @return: string
	 */
	public function keepSearchData($searchStDate, $searchEndDate)
    {
		data_set($this->_data, 'search.stDate', $searchStDate);
		data_set($this->_data, 'search.endDate', $searchEndDate);
	}
	
	public function getSearchStDate()
	{
		return data_get($this->_data, 'search.stDate', '');
	}
	
	public function getSearchEndDate()
	{
		return data_get($this->_data, 'search.endDate', '');
	}
	
	/* 門市 */
	/*
	public function getShopHeader()
    {
		# array = [shopid=>data]
		$before	= ['h1'=>'區域', 'h2'=>'門店代號', 'h3'=>'門店名稱'];
		$last 	= ['f1'=>'銷售總量', 'f2'=>'平均銷售數量'];
		$dateList = $this->_getDateRangeList($this->data['statistics']['startDate'], $this->data['statistics']['endDate']);
		
		return array_merge($before, $dateList, $last);
    }*/
	
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
	
	/* Form Style */
	public function getBreadcrumb()
    {
		return data_get($this->_data, 'config.name', 'UNKNOW');
	}
	
	public function getSaleDate()
    {
		return data_get($this->_data, 'config.saleDate', '');
	}
	
	public function getSaleEndDate()
    {
		return data_get($this->_data, 'config.saleEndDate', '');
	}
}