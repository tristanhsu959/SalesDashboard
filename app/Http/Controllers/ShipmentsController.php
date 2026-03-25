<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ShipmentsService;
use App\Services\Shipments\ShipmentsByNameService;
use App\ViewModels\ShipmentsViewModel;
use App\Enums\Brand;
use App\Enums\FormAction;
use App\Enums\Functions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ShipmentsController extends Controller
{
	public function __construct(protected ShipmentsService $_service, protected ShipmentsViewModel $_viewModel)
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
		
		return view('shipments.statistics')->with('viewModel', $this->_viewModel);
	}
	
	/* Search
	 * @params: request
	 * @return: view
	 */
	public function search(Request $request)
	{
		$brand 		= $this->_service->parsingBrand($request->segments());
		$function 	= $this->_service->parsingFunction($brand);
		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData(); #先init一次
		
		#query params
		$searchMode		= $request->input('searchMode', NULL);
		
		if ($searchMode == 'name')
			$this->_searchByName($request, $brand, $function);
		else if ($searchMode == 'type')
			$this->_searchByType($request);
		else
		{
			$this->_viewModel->fail('查詢參數錯誤');
			return view('shipments.statistics')->with('viewModel', $this->_viewModel);
		}
			
		
	}
	
	private function _searchByName(Request $request, $brand, $function)
	{
		$service = app(ShipmentsByNameService::class);
		
		$this->_viewModel->initialize($brand, $function);
		
		$searchStDate		= $request->input('searchStDate');
		$searchEndDate		= $request->input('searchEndDate');
		$searchProductName	= $request->input('searchProductName');
		
		$this->_viewModel->keepSearchDataByName($searchStDate, $searchEndDate, $searchProductName);
		
		$response = $service->getStatistics($brand, $function, $searchStDate, $searchEndDate, $searchProductName);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('shipments.statistics')->with('viewModel', $this->_viewModel); 
	}
	
	private function _searchByType(Request $request)
	{
		$searchStDate		= $request->input('searchStDate');
		$searchEndDate		= $request->input('searchEndDate');
		$searchProductType	= $request->input('searchProductType');
		
		$this->_viewModel->keepSearchDataByType($searchStDate, $searchEndDate, $searchProductType);
		
		$response = $this->_service->getStatisticsByType($this->_viewModel->brand, $searchStDate, $searchEndDate, $searchProductType);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('shipments.statistics')->with('viewModel', $this->_viewModel); 
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
			return view('shipments.statistics')->with('viewModel', $this->_viewModel);
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
