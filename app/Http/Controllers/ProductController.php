<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use App\ViewModels\ProductViewModel;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
	
	public function __construct(protected ProductService $_service, protected ProductViewModel $_viewModel)
	{
	}
	
	/* 列表
	 * @params: request
	 * @return: view
	 */
	public function list(Request $request)
	{
		$this->_viewModel->initialize(FormAction::LIST);
		#$this->_viewModel->keepSearchData();
		
		#$response = $this->_service->getList();
		
		/* if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
		{
			$this->_viewModel->success();
			$this->_viewModel->list = $response->data;
		} */
		
		return view('product/list')->with('viewModel', $this->_viewModel);
	}
	
	
	
	/* 新增Form
	 * @params: request
	 * @return: view
	 */
	public function showCreate(Request $request)
	{
		#initialize
		$this->_viewModel->initialize(FormAction::CREATE);
		$this->_viewModel->keepFormData(); #init
		$this->_viewModel->success();
		
		return view('product/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 新增 POST
	 * @params: request
	 * @return: view
	 */
	public function create(Request $request)
	{
		#fetch form data
		$id			= $request->input('id');
		$brand		= $request->integer('brand', 0);
		$name		= $request->input('name');
		$primaryNo	= $request->input('primaryNo');
		$secondaryNo= $request->input('secondaryNo');
		$tasteNo	= $request->input('tasteNo');
		$status		= $request->boolean('status', FALSE);
		
		#initialize
		$this->_viewModel->initialize(FormAction::CREATE);
		$this->_viewModel->keepFormData($id, $brand, $name, $primaryNo, $secondaryNo, $tasteNo, $status);
		
		#validate input
		$validator = Validator::make($request->all(), [
			'brand' => 'required|integer',
            'name' => 'required|max:15',
			'primaryNo' => 'required',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整');
			return view('product/detail')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->createProduct($brand, $name, $primaryNo, $secondaryNo, $tasteNo, $status);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('product/detail')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('product.list')->with('msg', '產品設定完成');
	}
	
	/* 編輯Form
	 * @params: request
	 * @params: int	id
	 * @return: view
	 */
	public function showUpdate(Request $request, $id)
	{
		#initialize
		$this->_viewModel->initialize(FormAction::UPDATE);
		
		if (empty($id))
			return redirect()->route('user.list')->with('msg', '身份識別ID為空值');
		
		$response = $this->_service->getUserById($id);
		
		if ($response->status === FALSE)
			return redirect()->route('user.list')->with('msg', $response->msg);
		
		$data = $response->data; 
		$this->_viewModel->keepFormData($data['userId'], $data['userAd'], $data['userDisplayName'], $data['userRoleId'], $data['updateAt']);
		$this->_viewModel->success();
		
		return view('user/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 編輯Form
	 * @params: request
	 * @return: view
	 */
	public function update(Request $request)
	{
		#fetch form data
		$id 		= $request->input('id');
		$adAccount	= $request->input('adAccount');
		$displayName= $request->input('displayName');
		$roleId		= $request->input('roleId');
		
		#initialize
		$this->_viewModel->initialize(FormAction::UPDATE);
		$this->_viewModel->keepFormData($id, $adAccount, $displayName, $roleId);
		
		if (empty($id))
			return redirect()->route('user.list')->with('msg', '身份識別ID為空值');
		
		$validator = Validator::make($request->all(), [
            'adAccount' => 'required|max:20',
			'roleId' => 'required|integer',
        ]);
 
        if (!$validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整');
			return view('user/detail')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->updateUser($id, $adAccount, $displayName, $roleId);
		
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
	 * @params: int	id
	 * @return: view
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
