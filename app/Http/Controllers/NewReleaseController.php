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
	
    /* 橙汁排骨
	 */
	public function getPorkRibsStatistics(Request $request)
	{
		$this->_viewModel->initialize(FormAction::List);
		
		#取新品config用
		$segment = $request->segment(2);
		$configKey = $this->_service->convertConfigKey($segment);
		
		$statistics = $this->_service->getStatistics($configKey);
		$this->_viewModel->segment = $segment;
		
		if ($statistics === FALSE)
			$this->_viewModel->fail('讀取資料發生錯誤');
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $statistics;
	
		return view('new_release.new_release')->with('viewModel', $this->_viewModel);
	}
	
	/* 牛三寶
	 */
	public function getTomatoBeefStatistics(Request $request)
	{
		$this->_viewModel->initialize(FormAction::List);
		
		#取新品config用		
		$segment = $request->segment(2);
		$configKey = $this->_service->convertConfigKey($segment);
		
		$statistics = $this->_service->getStatistics($configKey);
		$this->_viewModel->segment = $segment;
		
		if ($statistics === FALSE)
			$this->_viewModel->fail('讀取資料發生錯誤');
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $statistics;
		
		return view('new_release.new_release')->with('viewModel', $this->_viewModel);
	}
}
