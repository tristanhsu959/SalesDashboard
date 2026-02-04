<?php

namespace App\Http\Controllers\BuyGood;

use App\Http\Controllers\Controller;
use App\Services\PurchaseService;
use App\ViewModels\BuyGood\PurchaseViewModel as BfPurchaseViewModel;
use App\Enums\FormAction;
use App\Enums\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

#目前只先提供梁社漢
class PurchaseController extends Controller
{
	public function __construct(protected PurchaseService $_service, protected BfPurchaseViewModel $_viewModel)
	{
	}
	
	public function showSearch(Request $request)
	{
		$this->_viewModel->initialize(FormAction::LIST);
		
		#Status is NULL
		return view('purchase.bg_list')->with('viewModel', $this->_viewModel);
	}
	
	/* Search
	 * @params: request
	 * @return: view
	 */
	public function search(Request $request)
	{
		$searchStDate	= $request->input('searchStDate');
		$searchEndDate	= $request->input('searchEndDate');
		
		$this->_viewModel->initialize(FormAction::LIST);
		$this->_viewModel->keepSearchData($searchStDate, $searchEndDate);
	
		$response = $this->_service->search(Brand::BUYGOOD, $searchStDate, $searchEndDate);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('purchase.bg_list')->with('viewModel', $this->_viewModel);
	}
	
	/* Export
	 * @params: request
	 * @return: view
	 */
	public function export(Request $request, $token)
	{
		$this->_viewModel->initialize(FormAction::LIST);
		
		$response = $this->_service->export($token);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('purchase.bg_list')->with('viewModel', $this->_viewModel);
		}
		else
		{
			$fileName = $response->data;
			return Storage::disk('export')->download($fileName);
			#return response()->download($fileName);
		}
	}
}
