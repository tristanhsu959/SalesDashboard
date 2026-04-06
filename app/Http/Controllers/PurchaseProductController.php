<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PurchaseProductService;
use App\ViewModels\PurchaseProductViewModel;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class PurchaseProductController extends Controller
{
	
	public function __construct(protected PurchaseProductService $_service, protected PurchaseProductViewModel $_viewModel)
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
		
		return view('purchase_product/list')->with('viewModel', $this->_viewModel);
	}
	
	
	/* Setting Form
	 * @params: request
	 * @return: view
	 */
	public function showSetting(Request $request)
	{
		#initialize
		$this->_viewModel->initialize(FormAction::UPDATE);
		$this->_viewModel->keepFormData(); #init
		$this->_viewModel->success();
		
		return view('purchase_product/setting')->with('viewModel', $this->_viewModel);
	}
	
	/* 編輯Form
	 * @params: request
	 * @params: int	id
	 * @return: view
	 */
	public function showSetting(Request $request)
	{
		#initialize
		$this->_viewModel->initialize(FormAction::UPDATE);
		
		$response = $this->_service->getSetting();
		
		if ($response->status === FALSE)
			return redirect()->route('purchase_product.list')->with('msg', $response->msg);
		
		$data = $response->data; 
		
		$this->_viewModel->keepFormData($data['salesId'], $data['salesBrandId'], $data['salesName'], 
				$data['salesStatus'], $data['productIds'], $data['updateAt']);
		$this->_viewModel->success();
		
		return view('purchase_product/detail')->with('viewModel', $this->_viewModel);
	}
	
	/* 編輯Form
	 * @params: request
	 * @return: view
	 */
	public function update(Request $request)
	{
		$id				= $request->integer('id');
		$brandId		= $request->integer('brandId', 0);
		$productIds		= $request->array('productIds', []);
		$name			= $request->input('name');
		$status			= $request->boolean('status', FALSE);
		
		$this->_viewModel->initialize(FormAction::UPDATE);
		$this->_viewModel->keepFormData($id, $brandId, $name, $status, $productIds);
		
		if (empty($id))
			return redirect()->route('sales_setting.list')->with('msg', '銷售設定識別ID為空值');
		
		#validate input
		$validator = Validator::make($request->all(), [
			'id' 		=> 'required|integer',
			'brandId' 	=> 'required|integer',
			'productIds'=> 'required',
            'name' 		=> 'required|max:30',
        ]);
		
		if ($validator->fails()) 
		{
			$this->_viewModel->fail('資料輸入不完整');
			return view('sales_setting/detail')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->updateSetting($id, $brandId, $name, $status, $productIds);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('sales_setting/detail')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('sales_setting.list')->with('msg', '銷售設定編輯完成');
	}
	
	
}
