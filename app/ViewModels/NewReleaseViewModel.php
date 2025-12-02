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
		$this->_data['action'] 	= NULL; #enum form action
		$this->_data['status']	= FALSE;
		$this->_data['msg'] 	= '';
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
	public function initialize($action , $userId = 0)
	{
		#初始化各參數及Form Options
		$this->_data['action']	= $action;
		$this->_data['msg'] 	= '';
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
		return $this->_data['statistics']['productName'];
	}
}