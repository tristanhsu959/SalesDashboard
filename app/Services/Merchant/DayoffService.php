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
			$this->_userAreaIds = $currentUser->roleArea; */
			
			#執行統計
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
		$statistics['areaDayoff'] 	= $params->areaDayoff;
		$statistics['dayoff'] 		= $params->dayoff;
		$statistics['hasResult'] 	= FALSE;
		
		#無值不cache
		if (! empty($params->areaDayoff['store']))
		{
			$statistics['hasResult'] 	= TRUE;
			$statistics['exportToken'] 	= bin2hex($params->cacheKey); #hex2bin
			$statistics['exportName'] 	= '店休資訊';
			
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
			"money" => 11
		]
		*/
	
		try
		{
			$brand 		= $params->brand;
			$stDate		= Carbon::parse($params->stDate)->format('Y-m-d 00:00:00');
			$endDate 	= Carbon::parse($stDate)->addDay()->format('Y-m-d H:i:s');
			$userAreaIds= $params->userAreaIds;
			
			$result = $this->_repository->getDayoffList($brand, $stDate, $endDate, $userAreaIds);
			
			$params->storeList = $result;
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
	private function _outputReport($params)
	{
		try
		{
			#1.Build area statistics
			$this->_buildDayoffByArea($params);
			
			#2.Build store info
			$this->_buildDayoffByDetail($params);
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
	private function _buildDayoffByArea($params)
	{
		try
		{
			$storeData = $params->storeList;
			
			#Statistics dayoff
			$dayoffData = collect($storeData)->map(function($item, $key) {
				$area = AreaLib::toArea(intval($item['areaId']));
				$item['areaId']		= $area->value;
				$item['areaName']	= $area->label();
				
				return $item;
			})->sortBy('areaId')->groupBy('areaId')->values()->map(function($items, $key) {
				$temp['areaId']		= $items->pluck('areaId')->first();
				$temp['areaName']	= $items->pluck('areaName')->first();
				$temp['total']		= $items->count();
				
				$temp['dayoffCount']= $items->filter(function($item, $key){
					return intval($item['money']) <= 0;
				})->count();
				
				#$temp['percent']	= round(intval($temp['dayoffCount']) / intval($temp['total']) * 100, 2);
				
				return $temp;
			});
			
			#總計
			$summary['areaId']		= 0;
			$summary['areaName'] 	= '總計';
			$summary['total'] 		= $dayoffData->pluck('total')->sum();
			$summary['dayoffCount'] = $dayoffData->pluck('dayoffCount')->sum();
			#$summary['percent'] 	= round($dayoffData->pluck('percent')->sum() / $dayoffData->count(), 2);
			
			$area['header'] = ['區域', '店家數', '店休數']; /*['區域', '店家數', '店休數', '佔比'];*/
			$area['store'] = $dayoffData->merge([$summary])->toArray();
			
			$params->areaDayoff = $area;
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
	private function _buildDayoffByDetail($params)
	{
		try
		{
			$storeData = $params->storeList;
			
			$dayoffData = collect($storeData)->filter(function($item, $key){
					return intval($item['money']) <= 0;
			})->map(function($item, $key){
				$item['posId'] 	= (is_null($item['posId']) OR $item['posId'] == 'null') ? '' : $item['posId'];
				
				$area = AreaLib::toArea(intval($item['areaId']));
				$item['areaId']		= $area->value;
				$item['areaName']	= $area->label();
				
				return $item;
			})->sortBy('areaId')->values()->map(function($item, $key) {
				$temp['areaId']		= $item['areaId'];
				$temp['areaName'] 	= $item['areaName'];
				$temp['posId'] 		= $item['posId'];
				$temp['storeKey']	= PurchaseManager::buildStoreKey($item['storeNo']);
				$temp['storeName']	= $item['storeName'];
				
				return $temp;
			})->toArray(); 
			
			$info['header'] = ['區域', 'Pos店號', '門店代號', '門店名稱'];
			$info['store']	= $dayoffData;
			
			$params->dayoff = $info;
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
				unset($data['areaId']);
				$row =  Row::fromValues($data);
				$writer->addRow($row);
			}
			
			$exportStore = collect([$sourceData['dayoff']['header']])->merge($sourceData['dayoff']['store'])->toArray();
			$sheet = $writer->addNewSheetAndMakeItCurrent();
			$sheet->setName('店休-門店明細');
			
			foreach($exportStore as $key => $data)
			{
				unset($data['areaId']);
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
