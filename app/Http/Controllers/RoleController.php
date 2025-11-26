<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use App\Libraries\ResponseLib;
use App\Enums\RoleGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

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
		$data = $this->_service->getList();
		
		if ($data === FALSE)
			$response = ResponseLib::initialize()->fail('讀取身份清單發生錯誤')->get();
		else
			$response = ResponseLib::initialize($data)->success()->get();
		
		return view('role/list', $response);
	}
	
	/* 新增Form
	 * @params: request
	 * @return: array
	 */
	public function showCreate(Request $request)
	{
		#initialize
		$data['roleGroup'] 		= RoleGroup::cases(); #直接取enum
		$data['functionList'] 	= $this->_service->getAllMenu();
		
		$response = ResponseLib::initialize($data)->success()->get();
		
		return view('role/detail', $response);
	}
	
	/* 新增 POST
	 * @params: request
	 * @return: array
	 */
	public function create(Request $request)
	{
		#initialize
		$data['roleGroup'] 		= RoleGroup::cases(); #直接取enum
		$data['functionList']	 = $this->_service->getAllMenu();
		
		$validator = Validator::make($request->all(), [
            'name' => 'required|max:10',
			'group' => 'required|min:1',
        ]);
 
        if ($validator->fails()) 
		{
			$response = ResponseLib::initialize($data)->fail('資料輸入不完整')->get();
			return view('role/detail', $response);
		}
		
		$name 			= $request->input('name');
		$group 			= $request->input('group');
		$settingList	= $request->input('settingList');
		
		$result = $this->_service->createRole($name, $group, $settingList);
		
		if ($result === FALSE)
			$response = ResponseLib::initialize($data)->fail('身份新增失敗')->get();
		else
			$response = ResponseLib::initialize($data)->success('身份新增完成')->get();
		
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
