<?php

namespace App\Services\Merchant;

use App\Facades\AppManager;
use App\Facades\PurchaseManager;
use App\Repositories\MerchantRepository;
use App\Libraries\ResponseLib;
use App\Libraries\Purchase\AreaLib;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\Area;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

#partial Service
class InfoService
{
	public function __construct(protected MerchantRepository $_repository)
	{
		
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* Search data
	 * @params: array
	 * @return: array
	 */
	public function analysis($params)
	{
		try
		{
			#Get data
			$this->_getDataFromDB($params);
			
			$this->_outputReport($params);
			
			return $this->_generateStatistics($params);
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}
	
	/* Generate statistics data
	 * @params: object
	 * @return: array
	 */
	private function _generateStatistics($params)
	{
		$statistics['brandId']		= $params->brand->value; 
		$statistics['brandCode']	= $params->brand->code(); 
		$statistics['type']			= $params->type;
		$statistics['startDate'] 	= $params->stDate; 
		$statistics['info'] 		= $params->info;
		$statistics['hasResult'] 	= FALSE;
		
		#無值不cache
		if (! empty($params->info['store']))
		{
			$statistics['hasResult'] 	= TRUE;
			$statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			$statistics['exportName'] 	= '門店資訊';
			
			Cache::put($params->cacheKey, $statistics, now()->addMinutes(10));
		}
		
		return $statistics;
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* Get order data
	 * @params: 
	 * @return: array
	 */
	private function _getDataFromDB($params)
	{
		/*0 => array:11 [
			"areaId" => "21"
			"storeId" => "156"
			"storeNo" => "KH4000002"
			"storeName" => "台中柳川店"
			"posId" => "0388"
			"storePhone" => "04-2223-2283"
			"address" => "台中市中區民族路180號"
			"vatNumber" => "72318104"
			"salesName" => null
			"factoryName" => "高雄工廠"
			"carNo" => "C3"
  ]
		*/
	
		try
		{
			$storeList = $this->_repository->getStoreInfoList($params->brand, $params->userAreaIds);
			
			$params->storeList = $storeList;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取門店資料失敗');
		}
	}
	
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* 
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($params)
	{
		try
		{
			#1.Build store info
			$this->_buildInfo($params);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* Get order data
	 * @params: enums
	 * @params: date
	 * @params: date
	 * @params: array
	 * @return: array
	 */
	private function _buildInfo($params)
	{
		try
		{
			$storeList = $params->storeList;
			
			#先處理area為了可以排序
			$store = collect($storeList)->map(function($item, $key){
				$item['posId'] 		= (empty($item['posId']) OR $item['posId'] == 'null') ? '' : $item['posId'];
				$item['salesName']	= (empty($item['salesName']) OR $item['salesName'] == 'null') ? '' : $item['salesName'];
				
				$area = AreaLib::toArea(intval($item['areaId']));
				$item['areaId']		= $area->value;
				$item['areaName']	= $area->label();
				$item['storeKey'] 	= PurchaseManager::buildStoreKey($item['storeNo']);
				
				return $item;
			})->sortBy('areaId')->values()->map(function($item, $key) {
				$temp['areaName'] 	= $item['areaName'];
				#$temp['storeNo']	= $item['storeNo'];
				$temp['posId'] 		= $item['posId'];
				$temp['storeKey'] 	= $item['storeKey'];
				$temp['storeName']	= $item['storeName'];
				$temp['address']	= $item['address'];
				$temp['storePhone']	= $item['storePhone'];
				$temp['vatNumber']	= $item['vatNumber'];
				$temp['factoryName']= $item['factoryName'];
				$temp['warehouse']	= $item['warehouse'];
				$temp['carNo']		= $item['carNo'];
				$temp['salesName']	= $item['salesName'];
				
				return $temp;
			})->toArray(); 
			
			$info['header'] = ['區域', 'Pos店號', '門店代號', '門店名稱', '地址', '電話', '統一編號', '出貨工廠', '出貨倉別', '車次', '督導'];
			$info['store']	= $store;
			
			$params->info = $info;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('建立門店資料失敗');
		}
	}
	
	/* Export data
	 * @params: array
	 * @return: array
	 */
	public function export($sourceData)
	{
		try
		{
			#Build export data for sheets
			$export = collect([$sourceData['info']['header']])->merge($sourceData['info']['store'])->toArray();
			
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate']], '?_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			$sheet = $writer->getCurrentSheet();
			$sheet->setName($sourceData['exportName']);
				
			foreach($export as $key => $data)
			{
				$row =  Row::fromValues($data);
				$writer->addRow($row);
			}
			
			$writer->close();
			return ResponseLib::initialize($fileName)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize('檔案下載失敗，請重新查詢')->fail();
		}
	}
}
