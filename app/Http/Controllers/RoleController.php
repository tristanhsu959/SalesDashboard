<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use App\Libraries\ResponseLib;
use App\ViewModels\RoleViewModel;
use App\Enums\RoleGroup;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
	private $_service;
	private $_viewModel;
	
	public function __construct(RoleService $roleService, RoleViewModel $roleViewModel)
	{
		$this->_service		= $roleService;
		$this->_viewModel 	= $roleViewModel;
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
		
		$this->_viewModel->response = $response;
		$this->_viewModel->action = FormAction::List;
	
		return view('role/list', ['viewModel' => $this->_viewModel]);
	}
	
	/* 新增Form
	 * @params: request
	 * @return: array
	 */
	public function showCreate(Request $request)
	{
		#initialize
		$this->_viewModel->action 		= FormAction::CREATE;
		$this->_viewModel->roleGroup 	= RoleGroup::cases();
		$this->_viewModel->functionList = $this->_service->getAllMenu();
		
		$this->_viewModel->response = ResponseLib::initialize()->success()->get();
		
		return view('role/detail', ['viewModel' => $this->_viewModel]);
	}
	
	/* 新增 POST
	 * @params: request
	 * @return: array
	 */
	public function create(Request $request)
	{
		#initialize
		$this->_viewModel->action 		= FormAction::CREATE;
		$this->_viewModel->roleGroup 	= RoleGroup::cases();
		$this->_viewModel->functionList = $this->_service->getAllMenu();
		
		#validate input
		$validator = Validator::make($request->all(), [
            'name' => 'required|max:10',
			'group' => 'required|min:1',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->response = ResponseLib::initialize($data)->fail('資料輸入不完整')->get();
			return view('role/detail', ['viewModel' => $this->_viewModel]);
		}
		
		#fetch form data
		$name 			= $request->input('name');
		$group 			= $request->input('group');
		$settingList	= $request->input('settingList');
		
		$result = $this->_service->createRole($name, $group, $settingList);
		
		if ($result === FALSE)
			$this->_viewModel->response = ResponseLib::initialize()->fail('身份新增失敗')->get();
		else
			$this->_viewModel->response = ResponseLib::initialize()->success('身份新增完成')->get();
		
		return view('role/detail', ['viewModel' => $this->_viewModel]);
	}
	
	/* 編輯Form
	 * @params: request
	 * @return: array
	 */
	public function showUpdate(Request $request, $id)
	{
		#initialize
		$this->_viewModel->roleId 		= $id;
		$this->_viewModel->action 		= FormAction::UPDATE;
		$this->_viewModel->roleGroup 	= RoleGroup::cases();
		$this->_viewModel->functionList = $this->_service->getAllMenu();
		
		if (empty($id))
		{
			$this->_viewModel->response = ResponseLib::initialize()->fail('身份識別ID為空值')->get();
			return view('role/detail', ['viewModel' => $this->_viewModel]);
		}
		
		$data['roleData'] = $this->_service->getRoleById($id);
		$this->_viewModel->response = ResponseLib::initialize($data)->success()->get();
		#test
		return view('role/detail', ['viewModel' => $this->_viewModel]);
	}
	
	/* 編輯Form
	 * @params: request
	 * @return: array
	 */
	public function update(Request $request, $roleId)
	{
		$validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->response = ResponseLib::initialize($data)->fail('資料輸入不完整')->get();
			return view('role/detail', ['viewModel' => $this->_viewModel]);
		}
		
		$account = $request->input('ad_account');
		$password = $request->input('ad_password');
		
		$response = $this->_service->createRole();
		return view('role/detail', $response);
	}
	
}
