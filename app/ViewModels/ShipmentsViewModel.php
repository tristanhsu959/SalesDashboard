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
		$this->set('options.types', ['day' => '以日計算', 'month' => '以月計算']);
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
			FormAction::LIST	=> route(Str::replace('?', $brandCode, '?.new_releases.search')),
			FormAction::EXPORT	=> route(Str::replace('?', $brandCode, '?.new_releases.export'), ['token' => $this->statistics['exportToken']]),
		};
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function keepSearchData($searchType = 'day', $searchName = '', $searchSt = '', $searchEnd = '')
    {
		$this->set('search.type', $searchType);
		$this->set('search.name', $searchName);
		$this->set('search.start', $searchSt);
		$this->set('search.end', $searchEnd);
		$this->set('search.today', Carbon::now()->format('Y-m-d')); 
		$this->set('search.currentMonth', Carbon::now()->format('Y-m')); 
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