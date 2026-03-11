<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SalesService;
use App\ViewModels\SalesViewModel;
use App\Enums\Functions;
use App\Enums\FormAction;
use App\Enums\Brand;
use App\Exports\SalesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

#目前只先提供梁社漢
class SalesController extends Controller
{
	public function __construct(protected SalesService $_service, protected SalesViewModel $_viewModel)
	{
	}
	
	public function showSearch(Request $request)
	{
		$brand 		= $this->_service->parsingBrand($request->segments());
		$function 	= $this->_service->parsingFunction($brand);
		
		$this->_viewModel->initialize($brand, $function, FormAction::LIST);
		
		if (empty($brand) OR empty($function))
			$this->_viewModel->fail('無法識別ID');
		
		return view('sales.list')->with('viewModel', $this->_viewModel);
	}
	
	/* Search
	 * @params: request
	 * @return: view
	 */
	public function search(Request $request)
	{
		$brand 		= $this->_service->parsingBrand($request->segments());
		$function 	= $this->_service->parsingFunction($brand);
		
		$searchStDate	= $request->input('searchStDate');
		$searchEndDate	= $request->input('searchEndDate');
		
		$this->_viewModel->initialize($brand, $function, FormAction::LIST);
		$this->_viewModel->keepSearchData($searchStDate, $searchEndDate);
	
		$response = $this->_service->getStatistics($brand , $searchStDate, $searchEndDate);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('sales.list')->with('viewModel', $this->_viewModel);
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
			return view('sales.bg_list')->with('viewModel', $this->_viewModel);
		}
		else
		{
			$fileName = $response->data; 
			return Storage::disk('export')->download($fileName);
			#return response()->download($fileName);
		}
	}
}
