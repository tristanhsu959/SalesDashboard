<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SigninService;
use App\ViewModels\SigninViewModel;
use App\Libraries\ResponseLib;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SigninController extends Controller
{
	private $_service;
	private $_viewModel;
	
	public function __construct(SigninService $signinService, SigninViewModel $signinViewModel)
	{
		$this->_service 	= $signinService;
		$this->_viewModel 	= $signinViewModel;
	}
	
	public function showSignin()
	{
		return view('signin')->with('viewModel', $this->_viewModel);
	}
	
	/* 登入驗證
	 * @params: request
	 * @return: array
	 */
	public function signin(Request $request)
	{
		$adAccount = $request->input('adAccount');
		$adPassword = $request->input('adPassword');
		
		$this->_viewModel->initialize(FormAction::SIGNIN);
		$this->_viewModel->keepFormData($adAccount); #account only
		
		$validator = Validator::make($request->all(), [
            'adAccount' => 'required|max:20',
			'adPassword' => 'required|max:20',
        ]);
 
        if ($validator->fails())
		{
			$this->_viewModel->fail('登入失敗，輸入資料不完整');
			return view('signin')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->authSiginIn($adAccount, $adPassword);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('signin')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect('home');
	}
	
	/* 登出
	 * @params: request
	 * @return: array
	 */
	public function signout(Request $request)
	{
		$this->_viewModel->initialize(FormAction::SIGNIN);
		$this->_service->signout();
		
		return view('signin')->with('viewModel', $this->_viewModel);
	}
	
}