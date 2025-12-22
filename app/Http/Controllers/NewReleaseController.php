<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\NewReleaseService;
use App\ViewModels\NewReleaseViewModel;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

#目前邏輯相同, 故用同一個Controller
class NewReleaseController extends Controller
{
	private $_service;
	private $_viewModel;
	
	public function __construct(NewReleaseService $newReleaseService, NewReleaseViewModel $newReleaseViewModel)
	{
		$this->_service 	= $newReleaseService;
		$this->_viewModel 	= $newReleaseViewModel;
	}
	
	public function index(Request $request)
	{
		#取新品config用, 要存到Form
		$segment = $request->segment(2);
		$configKey = $this->_service->convertConfigKey($segment);
		
		$this->_viewModel->initialize(FormAction::List, $segment, $configKey);
		
		if (empty($configKey))
			$this->_viewModel->fail('無法識別產品ID');
		
		#Status is NULL
		return view('new_release.new_release')->with('viewModel', $this->_viewModel);
	}
	
	/* Search
	 * @params: request
	 * @return: array
	 */
	public function search(Request $request)
	{
		#query params
		$segment 	= $request->segment(2);
		$configKey 	= $this->_service->convertConfigKey($segment);
		$searchStDate	= $request->input('searchStDate');
		$searchEndDate	= $request->input('searchEndDate');
		
		$this->_viewModel->initialize(FormAction::List, $segment, $configKey);
		$this->_viewModel->keepSearchData($searchStDate, $searchEndDate);
		
		$response = $this->_service->getStatistics($configKey, $searchStDate, $searchEndDate);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('new_release.new_release')->with('viewModel', $this->_viewModel);
	}
	
	/* All Entry - 改為Search
	 */
	/*public function getStatistics(Request $request)
	{
		$this->_viewModel->initialize(FormAction::List);
		
		#取新品config用
		$segment = $request->segment(2);
		$this->_viewModel->segment = $segment;
		
		$this->_service->convertConfigKey($segment);
		
		$response = $this->_service->getStatistics();
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('new_release.new_release')->with('viewModel', $this->_viewModel);
	}*/
	
    /* 橙汁排骨
	 */
	/*public function getPorkRibsStatistics(Request $request)
	{
		$this->_viewModel->initialize(FormAction::List);
		
		#取新品config用
		$segment = $request->segment(2);
		$this->_viewModel->segment = $segment;
		
		$this->_service->convertConfigKey($segment);
		
		$response = $this->_service->getStatistics();
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('new_release.new_release')->with('viewModel', $this->_viewModel);
	}*/
	
	/* 牛三寶
	 */
	/*public function getTomatoBeefStatistics(Request $request)
	{
		$this->_viewModel->initialize(FormAction::List);
		
		#取新品config用		
		$segment = $request->segment(2);
		$this->_viewModel->segment = $segment;
		
		$this->_service->convertConfigKey($segment);
		
		$response = $this->_service->getStatistics();
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data;
		
		return view('new_release.new_release')->with('viewModel', $this->_viewModel);
	}*/
}
