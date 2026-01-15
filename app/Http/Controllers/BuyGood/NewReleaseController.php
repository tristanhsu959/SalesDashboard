<?php

namespace App\Http\Controllers\BuyGood;

use App\Http\Controllers\Controller;
use App\Services\NewReleaseLocalService;
use App\ViewModels\BuyGood\NewReleaseViewModel as BgNewReleaseViewModel;
use App\Enums\FormAction;
use App\Enums\Functions;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

#目前邏輯相同, 故用同一個Controller
class NewReleaseController extends Controller
{
	public function __construct(protected NewReleaseLocalService $_service, protected BgNewReleaseViewModel $_viewModel)
	{
	}
	
	public function porkRibs(Request $request)
	{
		return $this->_showIndex($request, Functions::BG_PORKRIBS);
	}
	public function tomatoBeef(Request $request)
	{
		return $this->_showIndex($request, Functions::BG_TOMATOBEEF);
	}
	public function eggTofu(Request $request)
	{
		return $this->_showIndex($request, Functions::BG_EGGTOFU);
	}
	public function porkGravy(Request $request)
	{
		return $this->_showIndex($request, Functions::BG_PORKGRAVY);
	}
	
	private function _showIndex(Request $request, $functionKey)
	{
		#取新品config用, 要存到Form
		$segment = Arr::last($request->segments());
		$configKey = $this->_service->convertConfigKey($segment);
		
		$this->_viewModel->initialize(FormAction::LIST, $configKey, $functionKey);
		
		if (empty($configKey))
			$this->_viewModel->fail('無法識別產品ID');
		
		#Status is NULL
		return view('new_release.bg_new_release')->with('viewModel', $this->_viewModel);
	}
	
	/* Search
	 * @params: request
	 * @return: view
	 */
	public function search(Request $request)
	{
		#query params
		$functionValue	= $request->input('functionKey');
		$configKey 		= $request->input('configKey');
		$searchStDate	= $request->input('searchStDate');
		$searchEndDate	= $request->input('searchEndDate');
		$functionKey	= Functions::getByValue($functionValue);
		
		$this->_viewModel->initialize(FormAction::LIST, $configKey, $functionKey);
		$this->_viewModel->keepSearchData($searchStDate, $searchEndDate);
	
		$response = $this->_service->getStatistics($configKey, $searchStDate, $searchEndDate);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('new_release.bg_new_release')->with('viewModel', $this->_viewModel);
	}
	
}
