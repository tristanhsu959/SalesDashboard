<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PurchaseSalesService;
use App\ViewModels\PurchaseSalesViewModel;
use App\Enums\Brand;
use App\Enums\FormAction;
use App\Enums\Functions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class PurchaseSalesController extends Controller
{
	public function __construct(protected PurchaseSalesService $_service, protected PurchaseSalesViewModel $_viewModel)
	{
	}
	
	public function showSearch(Request $request)
	{
		$brand 		= $this->_service->parsingBrand($request->segments());
		$function 	= $this->_service->parsingFunction($brand);
		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData();
		
		if (empty($brand) OR empty($function))
			$this->_viewModel->fail('無法識別ID');
		
		return view('purchase_sales.index')->with('viewModel', $this->_viewModel);
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
		$searchDate		= $request->input('searchDate');
		$searchStoreName= $request->input('searchStoreName');
		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData($searchDate, $searchStoreName);
	
		$response = $this->_service->getStoreList($brand, $searchDate, $searchStoreName);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('purchase_sales.index')->with('viewModel', $this->_viewModel);
	}
	
	/* Called by return to Search
	 * @params: request
	 * @return: view
	 */
	public function list(Request $request)
	{
		$brand 		= $this->_service->parsingBrand($request->segments());
		$function 	= $this->_service->parsingFunction($brand);
		
		$response = $this->_service->getLastSearchData($function);
		
		if ($response->status === FALSE)
			return redirect()->route(Str::replace('?', $brand->code(), '?.purchase_sales'));
		
		$searchDate 		= $response->data['searchDate'];
		$searchStoreName	= $response->data['searchStoreName'];
		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData($searchDate, $searchStoreName);
		$this->_viewModel->success();
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('purchase_sales.index')->with('viewModel', $this->_viewModel);
	}
	
	/* Search
	 * @params: request
	 * @return: view
	 */
	public function detail(Request $request)
	{
		$brand 		= $this->_service->parsingBrand($request->segments());
		$function 	= $this->_service->parsingFunction($brand);
		
		#query params
		$searchStoreId		= $request->input('searchStoreId');
		$searchDate			= $request->input('searchDate');
		$searchStoreName	= $request->input('searchStoreName');
		
		$this->_viewModel->initialize($brand, $function, FormAction::DETAIL);
		$this->_viewModel->keepSearchData($searchDate, $searchStoreName);
		
		if (empty($searchStoreId))
			return redirect()->back()->with('msg', '無法識別門店代碼');
		
		$response = $this->_service->getStatistics($brand, $searchDate, $searchStoreId);
		
		if ($response->status === FALSE)
			return redirect()->back()->with('msg', $response->msg);
		
		$this->_viewModel->success();
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('purchase_sales.detail')->with('viewModel', $this->_viewModel);
	}
	
	/* Export
	 * @params: request
	 * @return: view
	 */
	public function export(Request $request, $token)
	{
		$brand 		= $this->_service->parsingBrand($request->segments());
		$function 	= $this->_service->parsingFunction($brand);
		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData();
		
		$response = $this->_service->export($token);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('new_release.statistics')->with('viewModel', $this->_viewModel);
		}
		else
		{
			$fileName = $response->data; #成功data會存入檔名
			$filePath = Storage::disk('export')->path($fileName);
			
			if (file_exists($filePath)) {
				return response()->download($filePath)->deleteFileAfterSend();
			}
			#return Storage::disk('export')->download($fileName)->deleteFileAfterSend();
			#return response()->download($fileName);
		}
	}
}
