<?php

namespace App\Services\Sales;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Carbon\CarbonPeriod;
use Exception;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;

#partial Service
class AreaService
{
	public function __construct()
	{
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* 區域彙總
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	public function parsing($params)
	{
		/* Output
		"area" => [
			"大台北區" => [
				"totalQty" => 101
				"totalAmount" => 101
				"products" => productNo => [
					'productNo'
					'productName'
					'unit'
					'quantity'
					'amount'
				], ....
			]
			"大高雄區" => array:5 []
			"宜蘭區" => array:5 []
			"中彰投區" => array:5 []
			"雲嘉南區" => array:5 []
			"桃竹苗區" => array:5 []
		]
		*/
		
		$params->set('area.header', []);
		$params->set('area.data', []);
		$baseData = $params->baseData;
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return [];
		
		$header = [
					'areaName' 	=> '區域', 
					'shopCount'	=> '店家數',
					'products' 	=> $params->productHeader
				];
		$params->set('area.header', $header);
		
		$result = collect($baseData)->sortBy('areaId')->groupBy('areaId')->map(function($items, $key) {
			#區域總計
			$temp['areaName'] 	= $items->pluck('areaName')->get(0);
			$temp['shopCount']	= $items->pluck('shopId')->unique()->count(); #店家數
			
			#因補全門店會有key=0
			$temp['products']  	= $items->groupBy('productId')->map(function($items, $key){
				$price 		= $items->pluck('price')->first();
				$discount 	= $items->sum('discount');
				
				$temp['totalQty'] 	= $items->sum('qty');
				$temp['totalAmount']= round($price * $temp['totalQty'] + $discount, 2);
				
				return $temp;
			})->filter(function($item, $key){
				return $key != 0;
			})->toArray();
			
			return $temp;
		})->toArray();
		
		#這裏是依header
		$result['total']['areaName']	= '全區合計';
		$result['total']['shopCount']	= collect($baseData)->pluck('shopId')->unique()->count(); 
		$result['total']['products'] 	= collect($baseData)->groupBy('productId')->map(function($items, $key){
			$price 		= $items->pluck('price')->first();
			$discount 	= $items->sum('discount');
				
			$temp['totalQty'] 	= $items->sum('qty');
			$temp['totalAmount']= round($price * $temp['totalQty'] + $discount, 2);
			
			return $temp;
		})->filter(function($item, $key){
			return $key != 0;
		})->toArray();
		
		$params->set('area.data', array_filter($result));
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	public function buildExport($areaData)
	{
		#標頭都相同, 但要產生數量及金額兩個sheets
		$export['areaQty'] 		= [];
		$export['areaAmount'] 	= [];
		
		$header = Arr::flatten($areaData['header']);
		
		#Header相同
		$export['areaQty'][]	= $header;
		$export['areaAmount'][] = $header;
		
		foreach($areaData['data'] as $areaId => $data)
		{
			$rowQty		= [];
			$rowAmount 	= [];
			
			$rowQty[]	 = $data['areaName'];
			$rowAmount[] = $data['areaName'];
			
			$rowQty[]	 = $data['shopCount'];
			$rowAmount[] = $data['shopCount'];
			
			#須依header的順序取資料
			foreach($areaData['header']['products'] as $productId => $productName)
			{
				$rowQty[]	= intval(data_get($data, "products.{$productId}.totalQty", 0));
				$rowAmount[]= Number::currency(intval(data_get($data, "products.{$productId}.totalAmount", 0)), precision: 0);
			}
			
			$export['areaQty'][]	= $rowQty;
			$export['areaAmount'][] = $rowAmount;
		}
		
		return [$export['areaQty'], $export['areaAmount']] ;
	}
}
