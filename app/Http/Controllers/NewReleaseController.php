<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\NewReleaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

#目前邏輯相同, 故用同一個Controller
class NewReleaseController extends Controller
{
	private $_service;
	
	public function __construct(NewReleaseService $newReleaseService)
	{
		$this->_service = $newReleaseService;
	}
	
    /* 橙汁排骨
	 */
	public function getPorkRibsStatistics(Request $request)
	{
		#取新品config用
		$segment = $request->segment(2);
		$configKey = $this->_service->convertConfigKey($segment);
		
		$response = $this->_service->getStatistics($configKey);
		$response['segment'] = $segment;
		
		return view('new_release.new_release', $response);
	}
	
	/* 牛三寶
	 */
	public function getTomatoBeefStatistics(Request $request)
	{
		#取新品config用		
		$segment = $request->segment(2);
		$configKey = $this->_service->convertConfigKey($segment);
		
		$response = $this->_service->getStatistics($configKey);
		$response['segment'] = $segment;
		
		return view('new_release.new_release', $response);
	}
}
