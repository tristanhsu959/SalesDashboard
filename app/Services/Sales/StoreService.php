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
class StoreService
{
	public function __construct()
	{
	}
	
	/* ====================== 主流程 By Name ====================== */
	/* 店別每日銷售
	 * @params: array
	 * @params: int
	 * @return: array
	 */
	public function parsing($params)
	{
		/* 重整資料格式
		array:6 [
			"shopId" => "100001"
			"shopName" => "御廚中正南昌店"
			"areaId" => 1
			"areaName" => null
			"products" => array:5 [▼
				2 => array:1 [▼
					"productId" => 2
					"productName" => "橙汁排骨"
					"totalQty" => 15
					"totalAmount" => 2260.0
				]...
			]
		]
		*/
		$params->set('shop.header', []);
		$params->set('shop.data', []);
		$baseData = $params->baseData;
		
		#會有無設定區域權限的狀況, 須判別
		if (empty($baseData))
			return FALSE;
		
		#array_merge key不會保留
		$header = [
			'areaName'	=> '區域', 
			'shopId'	=> '門店代號', 
			'shopName' 	=> '門店名稱',
			'products' 	=> $params->productHeader
		];
		
		$params->set('shop.header', $header);
		
		$result = collect($baseData)->sortBy('areaId')->groupBy('shopId')->map(function($items, $key) {
			$temp['shopId'] 	= $items->pluck('shopId')->first();
			$temp['shopName'] 	= $items->pluck('shopName')->first();
			$temp['areaId'] 	= $items->pluck('areaId')->first();
			$temp['areaName'] 	= $items->pluck('areaName')->first();
				
			#因有補全的門店,故會有key=0的狀況	
			$temp['products'] = $items->groupBy('productId')->map(function($items, $key){
				$price 		= $items->pluck('price')->first();
				$discount 	= $items->sum('discount');
				
				$temp['totalQty']	= intval($items->sum('qty'));
				$temp['totalAmount']= round($price * $temp['totalQty'] + $discount, 2);
				
				return $temp;
				
			})->filter(function($item, $key){
				return $key != 0;
			})->toArray();
			
			return $temp;	
		})->values()->all();
		
		$params->set('shop.data', $result);
	}
	
	/* Build data for export
	 * @params: array
	 * @return: array
	 */
	public function buildExport($shopData)
	{
		#標頭都相同, 但要產生數量及金額兩個sheets
		$export['shopQty'] 		= [];
		$export['shopAmount'] 	= [];
		
		$header = Arr::flatten($shopData['header']);
		
		#Header相同
		$export['shopQty'][]	= $header;
		$export['shopAmount'][] = $header;
		
		foreach($shopData['data'] as $index => $data)
		{
			$rowQty		= [];
			$rowAmount 	= [];
			
			$rowQty[]	= $data['areaName'];
			$rowQty[]	= $data['shopId'];
			$rowQty[]	= $data['shopName'];
			
			$rowAmount[]= $data['areaName'];
			$rowAmount[]= $data['shopId'];
			$rowAmount[]= $data['shopName'];
			
			foreach($shopData['header']['products'] as $productId => $productName)
			{
				$rowQty[]	= intval(data_get($data, "products.{$productId}.totalQty", 0));
				$rowAmount[]= Number::currency(intval(data_get($data, "products.{$productId}.totalAmount", 0)), precision: 0);
			}
			
			$export['shopQty'][]	= $rowQty;
			$export['shopAmount'][] = $rowAmount;
		}
		
		return [$export['shopQty'], $export['shopAmount']] ;
	}
}
