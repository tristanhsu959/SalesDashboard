<?php

namespace App\ViewModels;

use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use App\ViewModels\Attributes\attrResponse;
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
	use attrStatus, attrActionBar, attrAllowAction, attrResponse;
	
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
		$type = [
			'date'	=> '日營收', #原計算方式
			'aov'	=> '客單統計(月)', #Average Order Value
		];
		$this->set('options.mode.type', $type);

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
	public function keepSearchData($type = 'date', $stDate = NULL, $endDate = NULL, $shopType = [], $shopName = '')
    {
		#Init default type
		$today = Carbon::now()->format('Y-m-d');
		$thisMonth = Carbon::now()->format('Y-m');
		
		#依brand預計不同
		if (empty($stDate) && empty($endDate) && empty($shopType))
			$shopType = ($this->brand == Brand::BAFANG) ? [1] : [1, 2]; #Default直營
		
		$this->set('search.type', $type);
		$this->set('search.stDate', $stDate ?? $today);
		$this->set('search.endDate', $endDate ?? $today);
		$this->set('search.shopType', $shopType);
		$this->set('search.shopName', $shopName);
		$this->set('search.today', $today);
		$this->set('search.thisMonth', $thisMonth);
	}
	
	/* Output js */
	public function searchFormData()
	{
		$this->set('search.formAction',  $this->getFormAction(FormAction::LIST));
		
		return $this->only('search', 'options');
	}
	
	/*有額外資訊能獨立加入,故要寫在Base*/
	public function responseData()
	{
		$response = $this->responseBaseData();
		
		$type = data_get($this->statistics, 'modeType', NULL);
		
		if ($type == 'date')
			$data = data_get($this->statistics, 'area.data', []);
		else
			$data = data_get($this->statistics, 'data', []);
		
		$response['hasResult'] = !empty($data);
		
		return $response;
	}
}