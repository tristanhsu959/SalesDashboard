<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use App\ViewModels\RoleViewModel;
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
		$this->_viewModel->initialize(FormAction::List);
		
		$data = $this->_service->getList();
		
		if ($data === FALSE)
			$this->_viewModel->fail('讀取身份清單發生錯誤');
		else
			$this->_viewModel->success();
		
		$this->_viewModel->list = $data;
		
		return view('role/list')->with('viewModel', $this->_viewModel);
	}
	
	/* 新增Form
	 * @params: request
	 * @return: array
	 */
	public function showCreate(Request $request)
	{
		#initialize
		$this->_viewModel->initialize(FormAction::CREATE);
		$this->_viewModel->success();
		
		return view('role/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 新增 POST
	 * @params: request
	 * @return: array
	 */
	public function create(Request $request)
	{
		#fetch form data
		$name 			= $request->input('name');
		$group 			= $request->input('group');
		$settingList	= $request->input('settingList');
		
		#initialize
		$this->_viewModel->initialize(FormAction::CREATE);
		$this->_viewModel->keepFormData($name, $group, $settingList);
		
		#validate input
		$validator = Validator::make($request->all(), [
            'name' => 'required|max:10',
			'group' => 'required|min:1',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整');
			return view('role/detail')->with('viewModel', $this->_viewModel);
		}
		
		$result = $this->_service->createRole($name, $group, $settingList);
		
		if ($result === FALSE)
		{
			$this->_viewModel->fail('新增身份失敗');
			return view('role/detail')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('role.list')->with('msg', '新增身份完成');;
	}
	
	/* 編輯Form
	 * @params: request
	 * @return: array
	 */
	public function showUpdate(Request $request, $id)
	{
		#initialize
		$this->_viewModel->initialize(FormAction::UPDATE, $id);
		
		if (empty($id))
			return redirect()->route('role.list')->with('msg', '身份識別ID為空值');
		
		$roleData = $this->_service->getRoleById($id);
		
		if ($roleData === FALSE)
			return redirect()->route('role.list')->with('msg', '讀取編輯資料發生錯誤');
		
		$this->_viewModel->roleData = $roleData; 
		$this->_viewModel->success();
		
		return view('role/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 編輯Form
	 * @params: request
	 * @return: array
	 */
	public function update(Request $request)
	{
		#fetch form data
		$id 			= $request->input('id');
		$name 			= $request->input('name');
		$group 			= $request->input('group');
		$settingList	= $request->input('settingList');
		
		#initialize
		$this->_viewModel->initialize(FormAction::UPDATE, $id);
		$this->_viewModel->keepFormData($name, $group, $settingList);
		
		if (empty($id))
			return redirect()->route('role.list')->with('msg', '身份識別ID為空值');
		
		$validator = Validator::make($request->all(), [
            'name' => 'required|max:10',
			'group' => 'required|min:1',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整');
			return view('role/detail')->with('viewModel', $this->_viewModel);
		}
		
		$result = $this->_service->updateRole($name, $group, $settingList, $id);
		
		if ($result === FALSE)
		{
			$this->_viewModel->fail('編輯身份失敗');
			return view('role/detail')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('role.list')->with('msg', '編輯身份完成');
	}
	
	/* 刪除
	 * @params: request
	 * @return: array
	 */
	public function delete(Request $request, $id)
	{
		#initialize
		$this->_viewModel->initialize(FormAction::DELETE, $id);
		
		/*跟validator整併即可*/
		if (empty($id))
			return redirect()->route('role.list')->with('msg', '身份識別ID為空值');
		
		$result = $this->_service->deleteRole($id);
		
		if ($result === FALSE)
			return redirect()->route('role.list')->with('msg', '刪除身份失敗');
		else
			return redirect()->route('role.list')->with('msg', '刪除身份完成');
	}
}
