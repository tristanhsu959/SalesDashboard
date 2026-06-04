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

class DailyRevenueViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct()
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
		#根據poserp.shop_kind
		$this->set('options.shopType', config('web.sales.shop.type'));
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
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.daily_revenue.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.daily_revenue.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: string
	 */
	public function keepSearchData($stDate = NULL, $endDate = NULL, $shopType = [], $shopName = '')
    {
		#Init default type
		$today = Carbon::now()->format('Y-m-d');
		
		if (empty($stDate) && empty($endDate) && empty($shopType))
		{
			$shopType = ($this->brand == Brand::BAFANG) ? [1] : [1, 2]; #Default直營
		}
		
		$this->set('search.stDate', $stDate ?? $today);
		$this->set('search.endDate', $endDate ?? $today);
		$this->set('search.shopType', $shopType);
		$this->set('search.shopName', $shopName);
		$this->set('search.today', $today);
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
		$brandCode 	= data_get($this->statistics, 'brandCode', NULL); #有執行查詢才會有brandId
		
		$info['status'] 		= $this->status();
		$info['exportAction'] 	= empty($token) ? '' : $this->getFormAction(FormAction::EXPORT);
		$info['hasData'] 		= ! empty($brandCode);
		$info['brandCode']		= $brandCode;
			
		return $info;
	}
	
	/*功能view所需的data*/
	public function statisticsData()
	{
		return $this->statistics;
	}
}