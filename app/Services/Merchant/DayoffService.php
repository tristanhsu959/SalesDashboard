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
use Illuminate\Support\Number;
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
class DayoffService
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
			$areaStores = $this->_getAreaStores();
			$dayoffData = $this->_getDataFromDB();
			
			return $this->_outputReport($areaStores, $dayoffData);
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}
	
	/* ====================== 主流程 End ====================== */
	
	/* 取區域有效門店數
	 * @params: 
	 * @return: array
	 */
	private function _getAreaStores()
	{
		try
		{
			$brand = Brand::tryFrom($this->_statistics['brandId']);
			$areaStores = $this->_repository->getActiveStoreId($brand, $this->_userAreaIds);
			
			return $areaStores;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取店休資料失敗');
		}
	}
	
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
			"posId" => "0388"
		]
		*/
	
		try
		{
			$brand = Brand::tryFrom($this->_statistics['brandId']);
			$stDate		= (new Carbon($this->_statistics['startDate']))->format('Y-m-d 00:00:00');
			$endDate 	= (new Carbon($this->_statistics['endDate']))->format('Y-m-d 23:59:59');
			
			$infoData = $this->_repository->getDayoffList($brand, $stDate, $endDate, $this->_userAreaIds);
			
			return $infoData;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('讀取店休資料失敗');
		}
	}
	
	/* ========================== 統計 ========================== */
	/* ========================================================== */
	/* Build report
	 * @params: array
	 * @params: array
	 * @return: array
	 */
	private function _outputReport($areaStores, $dayoffData)
	{
		try
		{
			#1.Build area statistics
			$this->_statistics['areaDayoff'] = $this->_buildAreaStores($areaStores, $dayoffData);
			
			#2.Build store info
			$this->_statistics['dayoff'] = $this->_buildDayoff($dayoffData);
			
			return $this->_statistics;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('解析報表資料發生錯誤');
		}
	}
	
	/* Build area dayoff stores
	 * @params: array
	 * @return: array
	 */
	private function _buildAreaStores($areaStores, $dayoffData)
	{
		try
		{
			#Statistics dayoff
			$dayoffStat = collect($dayoffData)->map(function($item, $key) {
				$area = AreaLib::toArea(intval($item['areaId']));
				$item['areaId']		= $area->value;
				$item['areaName']	= $area->label();
				
				return $item;
			})->groupBy('areaId')->map(function($items, $key) {
				return $items->pluck('storeId')->count();
			})->toArray();
			
			#須用原始data, 因需要area id
			$areaStat = collect($areaStores)->map(function($item, $key) {
				$area = AreaLib::toArea(intval($item['areaId']));
				$item['areaId']		= $area->value;
				$item['areaName']	= $area->label();
				
				return $item;
			})->sortBy('areaId')->groupBy('areaId')->values()->map(function($items, $key) use($dayoffStat) {
				$areaId 			= $items->pluck('areaId')->first();
				
				$temp['areaName']	= $items->pluck('areaName')->first();
				$temp['total']		= $items->pluck('storeId')->count();
				$temp['dayoffCount']= data_get($dayoffStat, $areaId, 0);
				$temp['percent']	= Number::percentage(intval($temp['dayoffCount']) / intval($temp['total']) * 100, precision: 2);
				
				return $temp;
			})->toArray(); 
			
			$area['header'] = ['區域', '店家數', '店休數', '佔比'];
			$area['store'] = $areaStat;
			
			return $area;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('統計區域店休資料失敗');
		}
	}
	
	/* Get order data
	 * @params: array
	 * @return: array
	 */
	private function _buildDayoff($dayoffData)
	{
		try
		{
			#先處理area為了可以排序
			$store = collect($dayoffData)->map(function($item, $key){
				$item['posId'] 	= (is_null($item['posId']) OR $item['posId'] == 'null') ? '' : $item['posId'];
				
				$area = AreaLib::toArea(intval($item['areaId']));
				$item['areaId']		= $area->value;
				$item['areaName']	= $area->label();
				
				return $item;
			})->sortBy('areaId')->values()->map(function($item, $key) {
				$temp['posId'] 		= $item['posId'];
				$temp['areaName'] 	= $item['areaName'];
				$temp['storeNo']	= $item['storeNo'];
				$temp['storeName']	= $item['storeName'];
				
				return $temp;
			})->toArray(); 
			
			$info['header'] = ['PosId', '區域', '門店代號', '門店名稱'];
			$info['store']	= $store;
			
			return $info;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('統計門店店休資料失敗');
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
			#Write export to file
			$brandName = Brand::tryFrom($sourceData['brandId'])->label();
			$fileName = Str::replaceArray('?', [$brandName, $sourceData['exportName'], $sourceData['startDate']], '?_?_?.xlsx');
			$filePath = Storage::disk('export')->path($fileName);
			
			$writer = new Writer();
			$writer->openToFile($filePath);
			
			#Build export data for sheets
			$exportArea = collect([$sourceData['areaDayoff']['header']])->merge($sourceData['areaDayoff']['store'])->toArray();
			$sheet = $writer->getCurrentSheet();
			$sheet->setName('店休-區域');
				
			foreach($exportArea as $key => $data)
			{
				$row =  Row::fromValues($data);
				$writer->addRow($row);
			}
			
			$exportStore = collect([$sourceData['dayoff']['header']])->merge($sourceData['dayoff']['store'])->toArray();
			$sheet = $writer->addNewSheetAndMakeItCurrent();
			$sheet->setName('店休-門店明細');
			
			foreach($exportStore as $key => $data)
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
