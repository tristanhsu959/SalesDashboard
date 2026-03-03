<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Exception;

class LunarRepository extends Repository
{
	#MSSQL|MySQL
	public function __construct()
	{
		
	}
	
	/* 取車次設定by date - 八方
	 * @params: datetime
	 * @return: array
	 */
	public function getBfSetting($assignDate)
	{
		/* 資料格式
		[
		  "shopNo" => "235001"
		  "carNo" => "1"
		]
		*/
		
		$db = DB::connection('LunarCarNo')->table('bf_car_no');
		$query = $db
				->select('shopNo', 'carNo')
				->where('assignDate', '=', $assignDate);

		return $query->get();
	}
	#御廚
	public function getBgSetting($assignDate)
	{
		$db = DB::connection('LunarCarNo')->table('bg_car_no');
		$query = $db
				->select('shopNo', 'carNo')
				->where('assignDate', '=', $assignDate);

		return $query->get();
	}
	
	/*======================= 八方 =======================*/
	/* 車次設定by date
	 * @params: array
	 * @return: array
	 */
	public function updateTpCarNo($tpSettings)
	{
		$db = $this->connectOrderTP();
		
		foreach($tpSettings as $setting)
		{
			$data['CarNo']	= $setting['carNo'];
			$db->table('AccBase')->where('Accno', '=', $setting['shopNo'])->update($data);
		}
		return TRUE;
	}
	
	/* 車次設定by date - 八方
	 * @params: array
	 * @return: array
	 */
	public function getTpCarNo($tpSettings)
	{
		$shopNos = Arr::pluck($tpSettings, 'shopNo');
		
		$db = $this->connectOrderTP('AccBase');
		$query = $db
				->select('AccNo', 'AccName', 'CarNo')
				->whereIn('AccNo', $shopNos)
				->orderBy('CarNo');
		#dd($query->toRawSql());
		return $query->get()->toArray();
	}
	
	
	/*======================= 御廚 =======================*/
	/* 車次設定by date
	 * @params: array
	 * @return: array
	 */
	public function updateTsCarNo($tsSettings)
	{
		$db = $this->connectOrderTS();
		
		foreach($tsSettings as $setting)
		{
			$data['CarNo']	= $setting['carNo'];
			$db->table('AccBase')->where('Accno', '=', $setting['shopNo'])->update($data);
		}
		return TRUE;
	}
	
	/* 車次設定by date - 八方
	 * @params: array
	 * @return: array
	 */
	public function getTsCarNo($tsSettings)
	{
		$shopNos = Arr::pluck($tsSettings, 'shopNo');
		
		$db = $this->connectOrderTS('AccBase');
		$query = $db
				->select('AccNo', 'AccName', 'CarNo')
				->whereIn('AccNo', $shopNos)
				->orderBy('CarNo');
		#dd($query->toRawSql());
		return $query->get()->toArray();
	}
	
	/*======================= 原設定 =======================*/
	/* 取車次設定by date - 八方
	 * @params: datetime
	 * @return: array
	 */
	public function getBfOriSetting($restoreDate)
	{
		$db = DB::connection('LunarCarNo')->table('bf_car_no as a');
		$query = $db
				->select('a.shopNo', 'b.carNo')
				->join('bf_car_original as b', 'b.shopNo', '=', 'a.shopNo')
				->where('a.assignDate', '=', $restoreDate);

		return $query->get();
	}
	#御廚
	public function getBgOriSetting($restoreDate)
	{
		$db = DB::connection('LunarCarNo')->table('bg_car_no as a');
		$query = $db
				->select('a.shopNo', 'b.carNo')
				->join('bg_car_original as b', 'b.shopNo', '=', 'a.shopNo')
				->where('a.assignDate', '=', $restoreDate);

		return $query->get();
	}
}
