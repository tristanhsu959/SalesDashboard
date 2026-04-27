<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\StoreService;
use App\ViewModels\StoreViewModel;
use App\Enums\Brand;
use App\Enums\FormAction;
use App\Enums\Functions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class StoreController extends Controller
{
	public function __construct(protected StoreService $_service, protected StoreViewModel $_viewModel)
	{
	}
	
	public function storeInfo(Request $request)
	{
		#共用,不需授權
		$this->_viewModel->initialize();
		#$this->_viewModel->keepSearchData();
		
		return view('store_info')->with('viewModel', $this->_viewModel);
	}
	
	/* Search
	 * @params: request
	 * @return: view
	 */
	public function search(Request $request)
	{
		$brand 		= $this->_service->parsingBrand($request->segments());
		$function 	= $this->_service->parsingFunction($brand);
		
		#query params
		$searchStDate	= $request->input('searchStDate');
		$searchEndDate	= $request->input('searchEndDate');
		$searchShopType	= $request->input('searchShopType', array_keys(config('web.sales.shop.type'))); #未選取查全部
		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData($searchStDate, $searchEndDate, $searchShopType);
		
		$response = $this->_service->getStatistics($brand, $searchStDate, $searchEndDate, $searchShopType);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('daily_revenue.statistics')->with('viewModel', $this->_viewModel);
	}
	
	/* Export
	 * @params: request
	 * @return: view
	 */
	public function export(Request $request, $token)
	{
		$brand 		= $this->_service->parsingBrand($request->segments());
		$function 	= $this->_service->parsingFunction($brand);
		//$token 	= $request->query('token');
		
		$this->_viewModel->initialize($brand, $function);
		
		$response = $this->_service->export($token);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('new_release.statistics')->with('viewModel', $this->_viewModel);
		}
		else
		{
			$fileName = $response->data; 
			$filePath = Storage::disk('export')->path($fileName);
			
			if (file_exists($filePath)) {
				return response()->download($filePath)->deleteFileAfterSend();
			}
			#return Storage::disk('export')->download($fileName)->deleteFileAfterSend();
			#return response()->download($fileName);
		}
	}
}
