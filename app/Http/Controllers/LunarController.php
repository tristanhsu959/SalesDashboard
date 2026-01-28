<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Libraries\ResponseLib;
use App\Services\LunarService;
use App\ViewModels\LunarViewModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LunarController extends Controller
{
	public function __construct(protected LunarService $_service, protected LunarViewModel $_viewModel)
	{
	}
	
	/* Signin view
	 * @params: request
	 * @return: view
	 */
	public function index()
	{
		return view('lunar')->with('viewModel', $this->_viewModel);
	}
	
	/* 登入驗證
	 * @params: request
	 * @return: view
	 */
	public function assign(Request $request, $date)
	{
		$assignDate = $date;
		
		if (empty($assignDate))
 		{
			$this->_viewModel->fail('設定失敗');
			return view('lunar')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->assignCarNo($assignDate);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
		{	
			$this->_viewModel->success();
			$this->_viewModel->settings = $response->data;	
		}
		return view('lunar')->with('viewModel', $this->_viewModel);
	}
	
	/* Signout
	 * @params: request
	 * @return: view
	 */
	public function restore(Request $request, $date)
	{
		$restoreDate = $date;
		
		if (empty($restoreDate))
 		{
			$this->_viewModel->fail('設定失敗');
			return view('lunar')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->restoreCarNo($restoreDate);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
		{	
			$this->_viewModel->success();
			$this->_viewModel->settings = $response->data;	
		}
		return view('lunar')->with('viewModel', $this->_viewModel);
	}
	
}