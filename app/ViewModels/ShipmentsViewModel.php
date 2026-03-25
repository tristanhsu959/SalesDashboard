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
		$mode = [
			'name'	=> '產品名稱', 
			'type'	=> '產品類別',
		];
		
		$this->set('options.mode', $mode);
		
		$productTypes = $this->_service->getProductTypes($this->brand->value);
		$this->set('options.productTypes', $productTypes);
		
		#list($category, $products) = $this->_service->getCategoryAndProduct($this->brand->value);
		#$this->set('options.products', $products);
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
	 * @return: array
	 */
	public function keepSearchDataByName($searchStDate, $searchEndDate, $searchProductName)
    {
		$this->keepSearchData('name', $searchStDate, $searchEndDate, '', $searchProductName);
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function keepSearchDataByType($searchStDate, $searchEndDate, $searchProductType)
    {
		$this->keepSearchData('type', $searchStDate, $searchEndDate, $searchProductType, '');
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function keepSearchData($searchMode = 'name', $searchStDate = '', $searchEndDate = '', $searchProductType = '', $searchProductName = '')
    {
		$this->set('search.mode', $searchMode);
		$this->set('search.stDate', $searchStDate);
		$this->set('search.endDate', $searchEndDate);
		$this->set('search.productType', $searchProductType);
		$this->set('search.productName', $searchProductName);
		$this->set('search.today', Carbon::now()->format('Y-m-d')); 
	}
	
	public function isDataEmpty()
	{
		if (empty(Arr::collapse($this->statistics)))
			return TRUE;
		else
			return FALSE;
	}
	
	/* public function getAreaName($id)
	{
		return Area::tryFrom($id)->label();
	} */
	
	/* 時間Header, 顯示方式不同
	 * @params: boolean #default desc
	 * @return: array
	 */
	public function showHeaderYear($year)
	{
		$lastYear = $this->get('lastYear', NULL);
		
		if ($lastYear == $year)
			return '';
		else
		{
			$this->set('lastYear', $year);
			return $year;
		}
		
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