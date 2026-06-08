<?php

namespace App\Manager;

use App\Facades\PurchaseManager;
use App\Manager\Repositories\LegacyRepository;
use App\Enums\Brand;
use App\Enums\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/* Old Order sys Common */
class LegacyManager
{
	public function __construct(protected LegacyRepository $_repository)
	{
	}
	
	/* 取全部追加(Save to local scheduling會用到)
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getExtraData($stDate, $endDate)
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
		
		#*****排程會呼叫此Function*****
		$bafang 	= $this->getExtraDataByBafang($stDate, $endDate, FALSE); #false for all
		$buygood	= $this->getExtraDataByBuygood($stDate, $endDate, FALSE);
		
		$result = collect($bafang)->merge($buygood)->toArray();
		
		return $result;
	}
	
	/* 取追加
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getExtraDataByProduct($brand, $stDate, $endDate, $productCodes)
	{
		if ($brand == Brand::BAFANG)
			$data = $this->getExtraDataByBafang($stDate, $endDate, $productCodes);
		else if ($brand == Brand::BUYGOOD)
			$data = $this->getExtraDataByBuygood($stDate, $endDate, $productCodes);
		else
			$data = [];
		
		return $data;
	}
	
	/* 取追加(先全取再由各自功能過濾門店或其它條件)
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getExtraDataByBafang($stDate, $endDate, $productCodes)
	{
		$tp = $this->_repository->getTpExtraData($stDate, $endDate, $productCodes);
		$kh = $this->_repository->getKhExtraData($stDate, $endDate, $productCodes);
		
		#storeNo維持原樣不影響
		$tp = $tp->map(function($item, $key){
			$item['factoryNo'] 		= Factory::TP->value;
			$item['factoryName'] 	= Factory::TP->label();
			$item['expectedDate']	= Carbon::parse($item['expectedDate'])->format('Y-m-d');
			$item['isExtra'] 		= TRUE; #判別area權限用,因這裏沒有區域定義
			return $item;
		});
		
		$kh = $kh->map(function($item, $key){
			$item['factoryNo'] 		= Factory::KH->value;
			$item['factoryName'] 	= Factory::KH->label();
			$item['expectedDate']	= Carbon::parse($item['expectedDate'])->format('Y-m-d');
			$item['isExtra'] 		= TRUE;
			return $item;
		});
		
		$result = $tp->merge($kh)->toArray();
		
		return $result;
	}
	
	/* 取追加
	 * @params: datetime
	 * @params: datetime
	 * @return: array
	 */
	public function getExtraDataByBuygood($stDate, $endDate, $productCodes)
	{
		$ts = $this->_repository->getTsExtraData($stDate, $endDate, $productCodes);
		$rl = $this->_repository->getRlExtraData($stDate, $endDate, $productCodes);
		
		#storeNo維持原樣不影響
		$ts = $ts->map(function($item, $key){
			$item['factoryNo'] 		= Factory::TS->value;
			$item['factoryName'] 	= Factory::TS->label();
			$item['expectedDate']	= Carbon::parse($item['expectedDate'])->format('Y-m-d');
			$item['isExtra'] 		= TRUE;
			return $item;
		});
		
		$rl = $rl->map(function($item, $key){
			$item['factoryNo'] 		= Factory::RL->value;
			$item['factoryName'] 	= Factory::RL->label();
			$item['expectedDate']	= Carbon::parse($item['expectedDate'])->format('Y-m-d');
			$item['isExtra'] 		= TRUE;
			return $item;
		});
		
		$result = $ts->merge($rl)->toArray();
		
		return $result;
	}
	
	public function getFactoryNo($brandId)
	{
		$brand = Brand::tryFrom($brandId);
		if ($brandId == Brand::BAFANG->value)
			return [Factory::TP->value, Factory::KH->value];
		else
			return [Factory::TS->value, Factory::RL->value];
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
		#先取全部再過濾
		if ($brand == Brand::BAFANG)
			$data = $this->getExtraDataByBafang($stDate, $endDate, FALSE);
		else if ($brand == Brand::BUYGOOD)
			$data = $this->getExtraDataByBuygood($stDate, $endDate, FALSE);
		else
			$data = [];
		
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