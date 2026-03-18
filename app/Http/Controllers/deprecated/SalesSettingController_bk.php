<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SalesSettingService;
use App\ViewModels\SalesSettingViewModel;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

##### 銷售統計設定 #####
class SalesSettingController extends Controller
{
	
	public function __construct(protected SalesSettingService $_service, protected SalesSettingViewModel $_viewModel)
	{
	}
	
	/* 列表
	 * @params: request
	 * @return: view
	 */
	public function list(Request $request)
	{
		$this->_viewModel->initialize(FormAction::LIST);
		
		$response = $this->_service->getSettings();
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
		{
			$this->_viewModel->success();
			$this->_viewModel->settings = $response->data;
		}
		
		return view('sales_setting/list')->with('viewModel', $this->_viewModel);
	}
	
	/* Update setting
	 * @params: request
	 * @return: view
	 */
	public function update(Request $request)
	{
		$settings		= $request->array('settings');
		
		#不keep form data, 因列表即編輯頁, 避免誤認有設定
		$this->_viewModel->initialize(FormAction::LIST);
		
		#空值也要更新
		$response = $this->_service->updateSetting($settings);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('sales_setting/list')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect()->route('sales_setting.list')->with('msg', '銷售設定更新完成');
	}
}
