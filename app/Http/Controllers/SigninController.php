<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SigninService;
use App\Libraries\ResponseLib;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SigninController extends Controller
{
	private $_service;
	
	public function __construct(SigninService $signinService)
	{
		$this->_service = $signinService;
	}
	
	public function showSignin()
	{
		return view('signin');
	}
	
	/* 登入驗證
	 * @params: request
	 * @return: array
	 */
	public function signin(Request $request)
	{
		$validator = Validator::make($request->all(), [
            'adAccount' => 'required|max:20',
			'adPassword' => 'required|max:20',
        ]);
 
        if ($validator->fails()) 
			return redirect('signin')->with('msg', '登入失敗，帳號或密碼錯誤');
		
		
		$adAccount = $request->input('adAccount');
		$adPassword = $request->input('adPassword');
		
		$response = $this->_service->authSiginIn($adAccount, $adPassword);
		
		if ($response === FALSE)
			return redirect('signin')->with('msg', '登入失敗，帳號或密碼錯誤');
		else
			return redirect('home');
	}
	
}