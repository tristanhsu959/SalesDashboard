<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\HomeService;
use App\ViewModels\HomeViewModel;
use App\Enums\FormAction;
use Illuminate\Http\Request;

class HomeController extends Controller
{
	public function __construct(protected HomeService $_service, protected HomeViewModel $_viewModel)
	{
	}
	
	public function index(Request $request)
	{
		$this->_viewModel->initialize();
		
		$response = $this->_service->getStatistics();
		
		if ($response->status === FALSE)
			$this->_viewModel->fail($response->msg);
		else
			$this->_viewModel->success();
		
		$this->_viewModel->statistics = $response->data; 
		
		return view('home')->with('viewModel', $this->_viewModel);
	}
	
}