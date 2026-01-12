<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\ViewModels\AuthViewModel;
use App\Libraries\ResponseLib;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
	public function __construct(protected AuthService $_service, protected AuthViewModel $_viewModel)
	{
	}
	
	/* Signin view
	 * @params: request
	 * @return: view
	 */
	public function showSignin()
	{
		$this->_viewModel->action = FormAction::SIGNIN;
		return view('signin')->with('viewModel', $this->_viewModel);
	}
	
	/* 登入驗證
	 * @params: request
	 * @return: view
	 */
	public function signin(Request $request)
	{
		$account 	= $request->input('adAccount');
		$password	= $request->input('adPassword');
		
		$this->_viewModel->action = FormAction::SIGNIN;
		$this->_viewModel->keepFormData($account); #account only
		
		$validator = Validator::make($request->all(), [
            'adAccount' => 'required|max:20',
			'adPassword' => 'required|max:20',
        ]);
 
        if ($validator->fails())
		{
			$this->_viewModel->fail('登入失敗，帳號或密碼輸入不完整');
			return view('signin')->with('viewModel', $this->_viewModel);
		}
		
		$response = $this->_service->signin($account, $password);
		
		if ($response->status === FALSE)
		{
			$this->_viewModel->fail($response->msg);
			return view('signin')->with('viewModel', $this->_viewModel);
		}
		else
			return redirect('home');
	}
	
	/* Signout
	 * @params: request
	 * @return: view
	 */
	public function signout(Request $request)
	{
		$this->_viewModel->action = FormAction::SIGNIN;
		$this->_service->signout();
		
		return view('signin')->with('viewModel', $this->_viewModel);
	}
	
}