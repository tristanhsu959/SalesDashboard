<?php

namespace App\Services\Merchant;

use App\Facades\AppManager;
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
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

#partial Service
class InfoService
{
	private $_userAreaIds	= FALSE;
	private $_statistics	= [];
   
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
			/* 暫不判別
			$currentUser = AppManager::getCurrentUser();
			$this->_userAreaIds = $currentUser['roleArea']; */
			$this->_statistics = $params;
			
			#執行統計
			$infoData = $this->_getDataFromDB();
			
			return $this->_outputReport($infoData);
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* Get order data
	 * @params: 
	 * @return: array
	 */
	private function _getDataFromDB()
	{
		/*0 => array:11 [
			"areaId" => "21"
			"storeId" => "156"
			"storeNo" => "KH4000002"
			"storeName" => "台中柳川店"
			"postId" => "0388"
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
			$brand = Brand::tryFrom($this->_statistics['brandId']);
			$infoData = $this->_repository->getStoreInfoList($brand, $this->_userAreaIds);
			
			return $infoData;
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
	private function _outputReport($infoData)
	{
		try
		{
			#1.Build store info
			$this->_statistics['info'] = $this->_buildInfo($infoData);
			
			return $this->_statistics;
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
	private function _buildInfo($infoData)
	{
		try
		{
			#先處理area為了可以排序
			$store = collect($infoData)->map(function($item, $key){
				$item['postId'] 	= (is_null($item['postId']) OR $item['postId'] == 'null') ? '' : $item['postId'];
				$item['salesName']	= (is_null($item['salesName']) OR $item['salesName'] == 'null') ? '' : $item['salesName'];
				
				$area = AreaLib::toArea(intval($item['areaId']));
				$item['areaId']		= $area->value;
				$item['areaName']	= $area->label();
				
				return $item;
			})->sortBy('areaId')->values()->map(function($item, $key) {
				$temp['postId'] 	= $item['postId'];
				$temp['areaName'] 	= $item['areaName'];
				$temp['storeNo']	= $item['storeNo'];
				$temp['storeName']	= $item['storeName'];
				$temp['address']	= $item['address'];
				$temp['storePhone']	= $item['storePhone'];
				$temp['factoryName']= $item['factoryName'];
				$temp['carNo']		= $item['carNo'];
				$temp['salesName']	= $item['salesName'];
				
				return $temp;
			})->toArray(); 
			
			$info['header'] = ['PosId', '區域', '門店代號', '門店名稱', '地址', '電話', '出貨工廠', '車次', '督導'];
			$info['store']	= $store;
			
			return $info;
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
