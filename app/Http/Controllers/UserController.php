<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{
	private $_service;
	
	public function __construct(UserService $userService)
	{
		$this->_service = $userService;
	}
	
	/* 列表
	 * @params: request
	 * @return: array
	 */
	public function list(Request $request)
	{
		return view('user/list');
		// $validator = Validator::make($request->all(), [
            // 'ad_account' => 'required|max:20',
			// 'ad_password' => 'required|max:20',
        // ]);
 
        // if ($validator->fails()) 
			// return redirect('login')->with('msg', '登入失敗，帳號或密碼輸入錯誤');
		
		
		// $account = $request->input('ad_account');
		// $password = $request->input('ad_password');
		
		// $response = $this->_service->authUser($account, $password);
		
		// if ($response['status'] === FALSE)
			// return redirect('login')->with('msg', '登入失敗，帳號或密碼錯誤');
		// else
			// return redirect('home');
	}
	
	/* 新增
	 * @params: request
	 * @return: array
	 */
	public function create(Request $request)
	{
		$response = $this->_service->createUser();
		return view('user/detail', $response);
	}
}
