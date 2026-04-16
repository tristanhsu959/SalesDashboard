<?php

namespace App\ViewModels;

use App\Services\ShipmentsService;
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

class ShipmentsViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct(protected ShipmentsService $_service)
	{
		$this->function		= NULL;
		$this->action 		= FormAction::LIST; 
		$this->backRoute 	= '';
		$this->success();
	}
	
	/* initialize
	 * @params: enum
	 * @params: string
	 * @params: string
	 * @return: void
	 */
	public function initialize($brand , $function)
	{
		$this->brand	= $brand;
		$this->function = $function;
		$this->statistics = [];
		
		$this->_setOptions();
	}
	
	/* Form所屬的參數選項
	 * @params:  
	 * @return: void
	 */
	private function _setOptions()
	{
		$this->_setSearchMode();
		
		list($category, $products) = $this->_service->getEnableProducts($this->brand->value);
		$this->set('options.category', $category);
		$this->set('options.products', $products); 
	}
	
	/* 查詢選項
	 * @params:  
	 * @return: void
	 */
	private function _setSearchMode()
	{
		$type = [
			'store'		=> '依門店', 
			'factory'	=> '依工廠',
		];
		$this->set('options.mode.type', $type);
		
		$calc = [
			'day'	=> '以日計算', 
			'month'	=> '以月計算',
		];
		$this->set('options.mode.calc', $calc);

		$by = [
			'keyword'	=> '關鍵字查詢',
			'category'	=> '分類查詢', 
		];
		$this->set('options.mode.by', $by);
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
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.shipments.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.shipments.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function keepSearchData($params = [])
    {
		$this->set('search.stDate', data_get($params, 'searchStDate', ''));
		$this->set('search.endDate', data_get($params, 'searchEndDate', ''));
		$this->set('search.keyword', data_get($params, 'searchKeyword', ''));
		$this->set('search.category', data_get($params, 'searchCategory', ''));
		$this->set('search.shortCodes', data_get($params, 'searchShortCodes', []));
		$this->set('search.type', data_get($params, 'searchType', 'store'));
		$this->set('search.calc', data_get($params, 'searchCalc', 'day'));
		$this->set('search.by', data_get($params, 'searchBy', 'keyword'));
		$this->set('search.today', Carbon::now()->format('Y-m-d')); 
	}
	
	/* Partial view
	 * @params: string
	 * @return: string
	 */
	public function getPartialView()
	{
		$type = $this->get('search.type', NULL);
		
		return match($type)
		{
			'store'		=> 'shipments.store',
			'factory'	=> 'shipments.factory',	 
		};
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
		$id = $this->statistics['brandId'];
		return (Brand::tryFrom($id))->code();
	}
}