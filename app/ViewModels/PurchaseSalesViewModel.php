<?php

namespace App\ViewModels;

use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use App\Enums\Brand;
use App\Enums\Area;
use App\Enums\Functions;
use App\Enums\FormAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Fluent;

class PurchaseSalesViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct()
	{
		$this->function		= NULL;
		$this->action 		= FormAction::LIST; 
		$this->backRoute 	= '';
		$this->success();
		$this->extraBreadcrumb = '';
	}
	
	/* initialize
	 * @params: enum
	 * @params: string
	 * @params: string
	 * @return: void
	 */
	public function initialize($brand , $function, $action = FormAction::LIST)
	{
		$this->brand	= $brand;
		$this->function = $function;
		$this->action	= $action;
		$this->backRoute = Str::replace('?', $brand->code(), '?.purchase_sales.list');
		$this->statistics = [];
		
		$this->_setOptions();
	}
	
	/* Form所屬的參數選項
	 * @params:  
	 * @return: void
	 */
	private function _setOptions()
	{
		if ($this->action == FormAction::DETAIL)
			return TRUE;
		
		#List才需要
		$type = [
			'all'		=> '全部店家', 
			'area'		=> '找區域',
			'storeName'	=> '找店名',
		];
		$this->set('options.type', $type);
		
		$areaList = $this->getAuthAreaList();
		$this->set('options.areaList', $areaList);
	}
	
	/* Form submit action
	 * @params: 
	 * @return: string
	 */
	public function getFormAction($isExport = FALSE) : string
    {
		#因export不會有頁面
		$action = ($isExport) ? FormAction::EXPORT : $this->action;
		$brandCode = $this->brand->code();
		
		return match($action)
		{
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.purchase_sales.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.purchase_sales.export'), ['token' => $this->statistics['exportToken']]),
			default				=> '',
		};
	}
	
	/* Detail form submit action(因在List要取用,故要獨立出來)
	 * @params: 
	 * @return: string
	 */
	public function getDetailFormAction() : string
    {
		$brandCode = $this->brand->code();
		return route(Str::replace('?', $brandCode, '?.purchase_sales.detail'));
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: string
	 */
	public function keepSearchData($searchType = 'all', $searchDate = '', $searchAreaId = 0, $searchStoreName = '')
    {
		$today =  Carbon::now()->format('Y-m-d');
		$searchDate = empty($searchDate) ? $today : $searchDate;
		
		if (empty($searchAreaId))
			$areaName = '';
		else
			$areaName = (Area::tryfrom($searchAreaId))->label();
		
		$this->set('search.type', $searchType);
		$this->set('search.date', $searchDate);
		$this->set('search.areaId', $searchAreaId);
		$this->set('search.areaName', $areaName);
		$this->set('search.storeName', $searchStoreName);
		$this->set('search.today', $today);
	}
	
	public function isDataEmpty()
	{
		if (empty(Arr::collapse($this->statistics)))
			return TRUE;
		else
			return FALSE;
	}
	
	public function hasExportData()
	{
		if ($this->isDataEmpty() OR empty($this->statistics['exportToken']))
			return FALSE;
		else
			return TRUE;
	}
	
	public function getBrandCode()
	{
		return $this->brand->code();
	}
}