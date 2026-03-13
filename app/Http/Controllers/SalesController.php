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

class SalesController extends Controller
{
	public function __construct(protected SalesService $_service, protected SalesViewModel $_viewModel)
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
		
		return view('sales.statistics')->with('viewModel', $this->_viewModel);
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
		
		$this->_viewModel->initialize($brand, $function);
		$this->_viewModel->keepSearchData($searchStDate, $searchEndDate);
	
		$response = $this->_service->getStatistics($brand, $searchStDate, $searchEndDate);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; #失敗也要有預設值
		
		return view('sales.statistics')->with('viewModel', $this->_viewModel);
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
		
		$response = $this->_service->export($token);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('sales.statistics')->with('viewModel', $this->_viewModel);
		}
		else
		{
			$fileName = $response->data; 
			$filePath = Storage::disk('export')->path($fileName);
			
			if (file_exists($filePath)) {
				return response()->download($filePath)->deleteFileAfterSend();
			}
		}
	}
}
