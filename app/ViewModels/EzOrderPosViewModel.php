<?php

namespace App\ViewModels;

use App\Facades\AppManager;
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

class EzOrderPosViewModel extends Fluent
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
		/* $calc = [
			'day'	=> '日', 
			'week'	=> '週', 
			'month'	=> '月', 
		];
		
		$this->set('options.calc', $calc);
		*/
		
		$type = [
			'store'		=> '依門店', 
			#'area'		=> '找區域',
			#'storeName'	=> '找店名', #取消此條件
		];
		$this->set('options.type', $type);
		
		#區域選項須以使用者權限為主
		$currentUser = AppManager::getCurrentUser();
		$allowAreas = $currentUser->getAreaPermissionsMap();
		
		$this->set('options.areas', $allowAreas);
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
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.ezorder_pos.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.ezorder_pos.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: string
	 */
	public function keepSearchData($type = 'store', $stDate = NULL, $endDate = NULL)
    {
		#Init default type
		$today = Carbon::now()->format('Y-m-d');
		$thisMonth = Carbon::now()->format('Y-m');
		
		$this->set('search.type', $type);
		$this->set('search.stDate', $stDate ?? $today);
		$this->set('search.endDate', $endDate ?? $today);
		$this->set('search.today', $today);
		$this->set('search.thisMonth', $thisMonth);
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
			'store'	=> 'ezorder_pos.store',
 		};
	}
	
	/* Output js */
	public function searchFormData()
	{
		$this->set('search.formAction',  $this->getFormAction(FormAction::LIST));
		
		return $this->only('search', 'options');
	}
	
	/*依不同功能的額外資訊,共用的在baseResponse */
	public function responseData()
	{
		$response = $this->responseBaseData();
		return $response;
	}
}