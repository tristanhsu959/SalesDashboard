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
	public function getFormAction($formAction) : string
    {
		$brandCode = $this->brand->code();
		
		return match($formAction)
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
	public function keepSearchData($searchType = 'store', $searchCalc = 'day', $searchStDate = NULL, $searchEndDate = NULL, 
						$searchBy = 'keyword', $searchKeyword = '', $searchCategory = '', $searchShortCodes = [])
    {
		$today = Carbon::tomorrow()->format('Y-m-d');
		$searchStDate	= $searchStDate ?? $today;
		$searchEndDate 	= $searchEndDate ?? $today;
		
		$this->set('search.type', $searchType);
		$this->set('search.calc', $searchCalc);
		$this->set('search.stDate', $searchStDate);
		$this->set('search.endDate', $searchEndDate);
		$this->set('search.by', $searchBy);
		$this->set('search.keyword', $searchKeyword);
		$this->set('search.category', $searchCategory);
		$this->set('search.shortCodes', $searchShortCodes);
		$this->set('search.tomorrow', Carbon::tomorrow()->format('Y-m-d'));
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
	
	/* Output js */
	public function searchFormData()
	{
		$this->set('search.formAction',  $this->getFormAction(FormAction::LIST));
		
		return $this->only('search', 'options');
	}
	
	/*共用view所需的data*/
	public function responseData()
	{
		$token 		= data_get($this->statistics, 'exportToken', NULL);
		$brandCode 	= data_get($this->statistics, 'brandCode', NULL); #指有執行查詢才有存的brand
		
		$info['status'] 		= $this->status();
		$info['exportAction'] 	= empty($token) ? '' : $this->getFormAction(FormAction::EXPORT);
		$info['hasData'] 		= ! empty($brandCode); #因可能有些功能沒下載,故不使用token判別
		$info['brandCode']		= $brandCode;
		$info['hasFilter']		= ($this->search['type'] == 'store');
			
		return $info;
	}
	
	/*功能view所需的data*/
	public function statisticsData()
	{
		return $this->statistics;
	}
}