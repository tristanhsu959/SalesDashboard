<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\NewItemService;
use App\ViewModels\NewItemViewModel;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class NewItemController extends Controller
{
	
	public function __construct(protected NewItemService $_service, protected NewItemViewModel $_viewModel)
	{
	}
	
	/* 列表
	 * @params: request
	 * @return: view
	 */
	public function list(Request $request)
	{
		$this->_viewModel->initialize(FormAction::LIST);
		
		$response = $this->_service->getList();
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
		{
			$this->_viewModel->success();
			$this->_viewModel->list = $response->data;
		}
		
		return view('new_item/list')->with('viewModel', $this->_viewModel);
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
		
		return view('new_item/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 新增 POST
	 * @params: request
	 * @return: view
	 */
	public function create(Request $request)
	{
		#fetch form data
		$id				= $request->integer('id');
		$brand			= $request->integer('brand', 0);
		$productId		= $request->integer('productId', 0);
		$name			= $request->input('name');
		$saleDate		= $request->input('saleDate');
		$tasteKeyWord	= $request->input('tasteKeyWord');
		$status			= $request->boolean('status', FALSE);
		
		$this->_viewModel->initialize(FormAction::CREATE);
		$this->_viewModel->keepFormData($id, $brand, $productId, $name, $saleDate, $tasteKeyWord, $status);
		
		#validate input
		$validator = Validator::make($request->all(), [
			'brand' 	=> 'required|integer',
			'productId' => 'required|integer',
            'name' 		=> 'required|max:30',
			'saleDate' 	=> 'required|date_format:Y-m-d',
        ]);
 
        if ($validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整');
			return view('new_item/detail')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->createNewItem($id, $brand, $productId, $name, $saleDate, $tasteKeyWord, $status);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('new_item/detail')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('new_item.list')->with('msg', '新品設定完成');
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
			return redirect()->route('new_item.list')->with('msg', '新品識別ID為空值');
		
		$response = $this->_service->getNewItemById($id);
		
		if ($response->status === FALSE)
			return redirect()->route('new_item.list')->with('msg', $response->msg);
		
		$data = $response->data; 
		$this->_viewModel->keepFormData($data['newItemId'], $data['newItemBrand'], $data['newItemProductId'], $data['newItemName'], $data['newItemSaleDate'], $data['newItemTaste'],  $data['newItemStatus'], $data['updateAt']);
		$this->_viewModel->success();
		
		return view('new_item/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 編輯Form
	 * @params: request
	 * @return: view
	 */
	public function update(Request $request)
	{
		$id				= $request->integer('id');
		$brand			= $request->integer('brand', 0);
		$productId		= $request->integer('productId', 0);
		$name			= $request->input('name');
		$saleDate		= $request->input('saleDate');
		$tasteKeyWord	= $request->input('tasteKeyWord');
		$status			= $request->boolean('status', FALSE);
		
		$this->_viewModel->initialize(FormAction::UPDATE);
		$this->_viewModel->keepFormData($id, $brand, $productId, $name, $saleDate, $tasteKeyWord, $status);
		
		if (empty($id))
			return redirect()->route('new_item.list')->with('msg', '新品識別ID為空值');
		
		#validate input
		$validator = Validator::make($request->all(), [
			'id' => 'required|integer',
			'brand' 	=> 'required|integer',
			'productId' => 'required|integer',
            'name' 		=> 'required|max:30',
			'saleDate' 	=> 'required|date_format:Y-m-d',
        ]);
		
		if ($validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整');
			return view('new_item/detail')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->updateNewItem($id, $brand, $productId, $name, $saleDate, $tasteKeyWord, $status);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('new_item/detail')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('new_item.list')->with('msg', '新品編輯完成');
	}
	
	/* 刪除
	 * @params: request
	 * @params: int	id
	 * @return: view
	 */
	public function delete(Request $request, $id)
	{
		#initialize
		$this->_viewModel->initialize(FormAction::DELETE);
		
		/*跟validator整併即可*/
		if (empty($id))
			return redirect()->route('product.list')->with('msg', '產品識別ID為空值');
		
		$response = $this->_service->deleteProduct($id);
		
		if ($response->status === FALSE)
			return redirect()->route('product.list')->with('msg', $response->msg);
		else
			return redirect()->route('product.list')->with('msg', '產品刪除完成');
	}
}
