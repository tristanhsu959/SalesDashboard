<?php

namespace App\ViewModels;

use App\Services\SalesService;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use App\Enums\Brand;
use App\Enums\Functions;
use App\Enums\FormAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Fluent;

#銷售統計
class SalesViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct(protected SalesService $_service)
	{
		$this->function		= NULL;
		$this->action 		= FormAction::LIST; 
		$this->backRoute 	= '';
		$this->success();
		$this->statistics = [];
	}
	
	/* initialize
	 * @params: enum
	 * @params: string
	 * @params: string
	 * @return: void
	 */
	public function initialize($brand , $function)
	{
		$this->brand		= $brand;
		$this->function 	= $function;
		$this->statistics 	= [];
		
		$this->_setOptions();
	}
	
	/* Form所屬的參數選項
	 * @params:  
	 * @return: void
	 */
	private function _setOptions()
	{
		list($category, $products) = $this->_service->getEnableProducts($this->brand->value);
		
		$this->set('options.category', $category);
		$this->set('options.products', $products); 
	}
	
	/* Form submit action
	 * @params: 
	 * @return: string
	 */
	public function getFormAction($formAction) : string
    {
		$brandCode = $this->brand->code();
		
		return match($formAction)
		{
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.sales.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.sales.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep form search data
	 * @params: int
	 * @params: date
	 * @params: date
	 * @return: void
	 */
	public function keepSearchData($searchStDate = NULL, $searchEndDate = NULL, $searchCategory = '', $searchProductIds = [])
    {
		$today = now()->format('Y-m-d');
		
		$this->set('search.stDate', $searchStDate ?? $today); 
		$this->set('search.endDate', $searchEndDate ?? $today);
		$this->set('search.category', $searchCategory);
		$this->set('search.productIds', $searchProductIds);
		$this->set('search.today', $today);
	}
	
	/* Output js */
	public function searchFormData()
	{
		$this->set('search.formAction',  $this->getFormAction(FormAction::LIST));
		
		return $this->only('search', 'options');
	}
	
	public function statisticsData()
	{
		$token = data_get($this->statistics, 'exportToken', NULL);
		
		if (empty($token))
			$this->exportAction = '';
		else
			$this->exportAction = $this->getFormAction(FormAction::EXPORT);
		
		$this->status = $this->status();
		
		return $this->only('statistics', 'exportAction', 'status');
	}
}