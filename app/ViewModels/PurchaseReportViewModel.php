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

class PurchaseReportViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction, attrResponse;
	
	public function __construct()
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
		$type = [
			'performance'	=> '營運概況', 
		];

		$this->set('options.mode.type', $type);
		
		#區域選項須以使用者權限為主
		$currentUser = AppManager::getCurrentUser();
		$allowAreas = $currentUser->getAreaPermissionsMap();
		
		$this->set('options.mode.areaList', $allowAreas);
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
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.purchase_report.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.purchase_report.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function keepSearchData($searchType = 'performance', $searchStDate = '', $searchEndDate = '', $searchAreaIds = [], $searchProductCodes = [])
    {
		$this->set('search.type', $searchType);
		$this->set('search.stDate', $searchStDate);
		$this->set('search.endDate', $searchEndDate);
		$this->set('search.areaIds', $searchAreaIds);
		$this->set('search.productCodes', $searchProductCodes);
		$this->set('search.today', Carbon::now()->format('Y-m-d')); 
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
			'performance'	=> 'purchase_report.performance',
		};
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
		
		$data = data_get($this->statistics, 'report', []);
		$response['hasResult'] = !empty($data);
		
		return $response;
	}
}