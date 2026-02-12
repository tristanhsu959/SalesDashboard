<?php

namespace App\Http\Controllers\BaFang;

use App\Http\Controllers\Controller;
use App\Services\NewReleaseLocalService;
use App\ViewModels\Bafang\NewReleaseViewModel as BfNewReleaseViewModel;
use App\Enums\FormAction;
use App\Enums\Functions;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

#目前邏輯相同, 故用同一個Controller
class NewReleaseController extends Controller
{
	public function __construct(protected NewReleaseLocalService $_service, protected BfNewReleaseViewModel $_viewModel)
	{
	}
	
	public function beefShortRibs(Request $request)
	{
		return $this->_showIndex($request, Functions::BF_BEEFSHORTRIBS);
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
		return view('new_release.bf_new_release')->with('viewModel', $this->_viewModel);
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
	
		$response = $this->_service->getBfStatistics($configKey, $searchStDate, $searchEndDate);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('new_release.bf_new_release')->with('viewModel', $this->_viewModel);
	}
}
