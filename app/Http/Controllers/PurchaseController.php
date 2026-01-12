<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PurchaseService;
use App\ViewModels\PurchaseViewModel;
use App\Enums\FormAction;
use App\Enums\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

#目前只先提供梁社漢
class PurchaseController extends Controller
{
	public function __construct(protected PurchaseService $_service, protected PurchaseViewModel $_viewModel)
	{
	}
	
	public function showSearchBg(Request $request)
	{
		$this->_viewModel->initialize(FormAction::List, Brand::BUYGOOD->value);
		
		#Status is NULL
		return view('purchase.list')->with('viewModel', $this->_viewModel);
	}
	
	/* Search
	 * @params: request
	 * @return: view
	 */
	public function search(Request $request)
	{
		#query params
		$searchBrand 	= $request->input('searchBrand');
		$searchStDate	= $request->input('searchStDate');
		$searchEndDate	= $request->input('searchEndDate');
		
		$this->_viewModel->initialize(FormAction::List);
		$this->_viewModel->keepSearchData($searchBrand, $searchStDate, $searchEndDate);
	
		$response = $this->_service->search($searchBrand, $searchStDate, $searchEndDate);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('purchase.list')->with('viewModel', $this->_viewModel);
	}
}
