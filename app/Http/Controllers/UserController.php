<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\ViewModels\UserViewModel;
use App\Enums\FormAction;
use App\Enums\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
	private $_service;
	private $_viewModel;
	
	public function __construct(UserService $userService, UserViewModel $userViewModel)
	{
		$this->_service 	= $userService;
		$this->_viewModel 	= $userViewModel;
	}
	
	/* 列表
	 * @params: request
	 * @return: array
	 */
	public function list(Request $request)
	{
		$this->_viewModel->initialize(FormAction::List);
		
		$response = $this->_service->getList();
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
		{
			$this->_viewModel->success();
			$this->_viewModel->list = $response->data;
		}
		
		return view('user/list')->with('viewModel', $this->_viewModel);
	}
	
	/* Search
	 * @params: request
	 * @return: array
	 */
	public function search(Request $request)
	{
		$this->_viewModel->initialize(FormAction::List);
		
		#query params
		$searchAd	= $request->input('searchAd');
		$searchName	= $request->input('searchName');
		$searchArea	= $request->input('searchArea');
		
		$this->_viewModel->keepSearchData($searchAd, $searchName, $searchArea);
		
		$response = $this->_service->searchList($searchAd, $searchName, $searchArea);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
		{
			$this->_viewModel->success();
			$this->_viewModel->list = $response->data;
		}
		
		return view('user/list')->with('viewModel', $this->_viewModel);
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
		
		return view('user/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 新增 POST
	 * @params: request
	 * @return: array
	 */
	public function create(Request $request)
	{
		#fetch form data
		$adAccount	= $request->input('adAccount');
		$displayName= $request->input('displayName');
		$area		= $request->input('area');
		$role		= $request->input('role');
		
		#initialize
		$this->_viewModel->initialize(FormAction::CREATE);
		$this->_viewModel->keepFormData($adAccount, $displayName, $area, $role);
		
		#validate input
		$validator = Validator::make($request->all(), [
            'adAccount' => 'required|max:20',
			'role' => 'required|integer',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整');
			return view('user/detail')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->createUser($adAccount, $displayName, $area, $role);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('user/detail')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('user.list')->with('msg', '新增帳號完成');
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
			return redirect()->route('user.list')->with('msg', '身份識別ID為空值');
		
		$response = $this->_service->getUserById($id);
		
		if ($response->status === FALSE)
			return redirect()->route('user.list')->with('msg', $response->msg);
		
		$this->_viewModel->userData = $response->data; 
		$this->_viewModel->success();
		
		return view('user/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 編輯Form
	 * @params: request
	 * @return: array
	 */
	public function update(Request $request)
	{
		#fetch form data
		$id 		= $request->input('id');
		$adAccount	= $request->input('adAccount');
		$displayName= $request->input('displayName');
		$area		= $request->input('area');
		$role		= $request->input('role');
		
		#initialize
		$this->_viewModel->initialize(FormAction::UPDATE, $id);
		$this->_viewModel->keepFormData($adAccount, $displayName, $area, $role);
		
		if (empty($id))
			return redirect()->route('user.list')->with('msg', '身份識別ID為空值');
		
		$validator = Validator::make($request->all(), [
            'adAccount' => 'required|max:20',
			'role' => 'required|integer',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整');
			return view('user/detail')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->updateUser($adAccount, $displayName, $area, $role, $id);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('user/detail')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('user.list')->with('msg', '編輯帳號完成');
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
			return redirect()->route('user.list')->with('msg', '身份識別ID為空值');
		
		$response = $this->_service->deleteUser($id);
		
		if ($response->status === FALSE)
			return redirect()->route('user.list')->with('msg', $response->msg);
		else
			return redirect()->route('user.list')->with('msg', '刪除帳號完成');
	}
}
