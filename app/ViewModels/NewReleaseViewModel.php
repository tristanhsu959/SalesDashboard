<?php

namespace App\ViewModels;

use App\Services\NewReleaseService;
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

class NewReleaseViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct(protected NewReleaseService $_service)
	{
		$this->function		= NULL;
		$this->action 		= FormAction::LIST; #與breadcrumb有關
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
		$this->set('options.newReleaseProducts', $this->_service->getNewReleaseProducts($this->brand->value));
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
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.new_releases.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.new_releases.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: string
	 */
	public function keepSearchData($searchReleaseId = 0, $searchStDate = '', $searchEndDate = '')
    {
		$this->set('search.releaseId', $searchReleaseId);
		$this->set('search.stDate', $searchStDate);
		$this->set('search.endDate', $searchEndDate);
		$this->set('search.today', Carbon::now()->format('Y-m-d'));
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