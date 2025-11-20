<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Libraries\ResponseLib;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
	private $_service;
	
	public function __construct(AuthService $authService)
	{
		$this->_service = $authService;
	}
	
	public function index()
	{
		$msg = session('msg');
		
		#Return defatul
		if (empty($msg))
			$response = ResponseLib::initialize()->success()->get();
        else
			$response = ResponseLib::initialize()->fail($msg)->get();
		
		return view('login', $response);
	}
	
	/* 登入驗證
	 * @params: request
	 * @return: array
	 */
	public function authLogin(Request $request)
	{
		$validator = Validator::make($request->all(), [
            'ad_account' => 'required|max:20',
			'ad_password' => 'required|max:20',
        ]);
 
        if ($validator->fails()) 
			return redirect('login')->with('msg', '登入失敗，帳號或密碼輸入錯誤');
		
		
		$account = $request->input('ad_account');
		$password = $request->input('ad_password');
		
		$response = $this->_service->authUser($account, $password);
		
		if ($response['status'] === FALSE)
			return redirect('login')->with('msg', '登入失敗，帳號或密碼錯誤');
		else
			return redirect('home');
	}
	
}