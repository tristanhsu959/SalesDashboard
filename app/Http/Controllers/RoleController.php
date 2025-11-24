<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use App\Libraries\ResponseLib;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
	private $_service;
	
	public function __construct(RoleService $roleService)
	{
		$this->_service = $roleService;
	}
	
	/* 列表
	 * @params: request
	 * @return: array
	 */
	public function list(Request $request)
	{
		$response = $this->_service->getList();
		
		return view('role/list', $response);
	}
	
	/* 新增Form
	 * @params: request
	 * @return: array
	 */
	public function showCreate(Request $request)
	{
		$config['permissionList'] = $this->_service->getMenu();
		$response = ResponseLib::initialize($config)->success()->get();
		
		return view('role/detail', $response);
	}
	
	/* 新增
	 * @params: request
	 * @return: array
	 */
	public function create(Request $request)
	{
		$response = $this->_service->createRole();
		
		return view('role/detail', $response);
	}
	
	/* 編輯
	 * @params: request
	 * @return: array
	 */
	public function update(Request $request, $roleId)
	{
		// $validator = Validator::make($request->all(), [
            // 'ad_account' => 'required|max:20',
			// 'ad_password' => 'required|max:20',
        // ]);
 
        // if ($validator->fails()) 
			// return redirect('login')->with('msg', '登入失敗，帳號或密碼輸入錯誤');
		// $account = $request->input('ad_account');
		// $password = $request->input('ad_password');
		
		$response = $this->_service->createRole();
		return view('role/detail', $response);
	}
}
