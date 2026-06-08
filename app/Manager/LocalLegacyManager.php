<?php

namespace App\Manager;

use App\Facades\PurchaseManager;
use App\Manager\Repositories\LocalLegacyRepository;
use App\Enums\Brand;
use App\Enums\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/* Local extra order */
#取Local DB追加(因查詢舊系統效能差),正常狀況不需另外取
#但需待未來都在新系統建單,此功能就可以不用,故獨立寫,以方便未來抽離
class LocalLegacyManager
{
	public function __construct(protected LocalLegacyRepository $_repository)
	{
	}
	
	/* 取追加
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getExtraDataByProduct($brand, $stDate, $endDate, $productCodes)
	{
		$data = $this->_repository->getExtraData($brand->value, $stDate, $endDate, $productCodes);
		
		return $data;
	}
	
	/* 取追加ByPosId
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getExtraDataByStore($brand, $stDate, $endDate, $storeKey)
	{
		/*[
			"shortCode" => "3615"
			"productName" => "紹辣醬"
			"storeNo" => "1001"
			"expectedDate" => "2026-06-01"
			"qty" => "50.00"
			"amount" => "4500.00"
			"factoryNo" => "TW_TP"
			"factoryName" => "淡水總廠"
			"isExtra" => true
		]
		*/
		$data = $this->_repository->getExtraData($brand->value, $stDate, $endDate, FALSE);
		
		$data = collect($data)->map(function($item, $key){
			$temp['expectedDate'] 	= $item['expectedDate'];
			$temp['shortCode'] 		= $item['shortCode'];
			$temp['productName'] 	= $item['productName'];
			$temp['qty'] 			= $item['qty'];
			$temp['amount'] 		= $item['amount'];
			$temp['storeKey'] 		= PurchaseManager::buildStoreKey($item['storeNo']);
			
			return $temp;
		})->filter(function($item, $key) use($storeKey){
			return $item['storeKey'] == $storeKey;
		})->toArray();
			
		return $data;
	}
	
	
}