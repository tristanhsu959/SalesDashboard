<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Libraries\ResponseLib;
use App\Services\LunarService;
use App\ViewModels\LunarViewModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
class LunarController extends Controller
{
	public function __construct(protected LunarService $_service, protected LunarViewModel $_viewModel)
	{
	}
	
	/* Signin view
	 * @params: request
	 * @return: view
	 */
	public function index()
	{
		$a = DB::connection('BGPosErp')->table($table)->lock('WITH(NOLOCK)');
		dd($a);
		return view('lunar')->with('viewModel', $this->_viewModel);
	}
	
	/* Search car no
	 * @params: request
	 * @return: view
	 */
	public function search(Request $request, $date)
	{
		$searchDate = $date;
		
		if (empty($searchDate))
 		{
			$this->_viewModel->fail('無時間參數');
			return view('lunar')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->searchCarNo($searchDate);
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
		{	
			$this->_viewModel->success();
			$this->_viewModel->settings = $response->data;	
		}
		return view('lunar')->with('viewModel', $this->_viewModel);
	}
	
	/* Set car no
	 * @params: request
	 * @return: view
	 */
	public function assign(Request $request, $date)
	{
		$assignDate = $date;
		
		if (empty($assignDate))
			return redirect()->route('lunar.index')->with('msg', '無時間參數');
		
		$response = $this->_service->assignCarNo($assignDate);
		
		if ($response->status === FALSE)
			$msg = $response->msg;
		else
			$msg = '';
		
		return redirect()->route('lunar.search', ['date' => $date])->with('msg', $msg);
	}
	
	/* Restore car no
	 * @params: request
	 * @return: view
	 */
	public function restore(Request $request, $date)
	{
		$restoreDate = $date;
		
		if (empty($restoreDate))
			return redirect()->route('lunar.index')->with('msg', '無時間參數');
		
		$response = $this->_service->restoreCarNo($restoreDate);
		
		if ($response->status === FALSE)
			$msg = $response->msg;
		else
			$msg = '';
		
		return redirect()->route('lunar.search', ['date' => $date])->with('msg', $msg);
	}
	
}