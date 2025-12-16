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
	
	/* All Entry
	 */
	public function getStatistics(Request $request)
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
	}
	
    /* 橙汁排骨
	 */
	public function getPorkRibsStatistics(Request $request)
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
	}
	
	/* 牛三寶
	 */
	public function getTomatoBeefStatistics(Request $request)
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
	}
}
