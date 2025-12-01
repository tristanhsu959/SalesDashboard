<?php

namespace App\ViewModels;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class NewReleaseViewModel
{
	private $title = '新品銷售';
	private $data = [];
	
	public function __construct()
	{
		#initialize
		$this->data['action'] 	= NULL; #enum form action
		$this->data['status']	= FALSE;
		$this->data['msg'] 		= '';
	}
	
	public function __set($name, $value)
    {
		$this->data[$name] = $value;
    }
	
	public function __get($name)
    {
		return $this->data[$name];
	}
	
	/* 須有isset, 否則empty()會判別錯誤 */
	public function __isset($name)
    {
		return array_key_exists($name, $this->data);
	}
	
	/* Status / Msg
	 * @params: 
	 * @return: boolean
	 */
	public function success($msg = NULL)
	{
		$this->data['status'] = TRUE;
		$this->data['msg'] = $msg ?? '';
	}
	
	public function fail($msg)
	{
		$this->data['status'] 	= FALSE;
		$this->data['msg'] 		= $msg;
	}
	
	/* initialize
	 * @params: enum
	 * @params: int
	 * @return: void
	 */
	public function initialize($action , $userId = 0)
	{
		#初始化各參數及Form Options
		$this->data['action']	= $action;
		$this->data['msg'] 		= '';
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
		$st = Carbon::create($this->data['statistics']['startDate']);
		$end = Carbon::create($this->data['statistics']['endDate']);
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
		return $this->data['statistics']['productName'];
	}
}