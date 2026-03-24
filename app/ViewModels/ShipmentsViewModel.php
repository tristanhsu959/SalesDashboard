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
		$modes = [
			'date'		=> '依日/月統計', 
			'area' 		=> '依區域統計',
			'factory' 	=> '依工廠統計',
			'shop' 		=> '依門店統計',
		];
		
		$this->set('options.modes', $modes);
		
		list($category, $products) = $this->_service->getCategoryAndProduct($this->brand->value);
		$this->set('options.category', $category);
		$this->set('options.products', $products);
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
	public function keepSearchData($searchMode = 'date', $searchStDate = '', $searchEndDate = '', $searchCatNo = 0, $searchProductNos = [])
    {
		$this->set('search.mode', $searchMode);
		$this->set('search.stDate', $searchStDate);
		$this->set('search.endDate', $searchEndDate);
		$this->set('search.catNo', $searchCatNo);
		$this->set('search.productNos', $searchProductNos);
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