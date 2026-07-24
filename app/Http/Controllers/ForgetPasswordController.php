<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ForgetPasswordService;
use App\ViewModels\ForgetPasswordViewModel;
use App\Libraries\ResponseLib;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ForgetPasswordController extends Controller
{
	public function __construct(protected ForgetPasswordService $_service, protected ForgetPasswordViewModel $_viewModel)
	{
	}
	
	/* 忘記密碼 Called by login view
	 * @params: request
	 * @return: view
	 */
	public function sendLink(Request $request)
	{
		#此功能不存viewModel
		$account = $request->input('account');
		
		$validator = Validator::make($request->all(), [
            'account' => 'required|max:20',
		]);
		
		if ($validator->fails())
			return redirect()->route('signin')->with('msg', '忘記密碼連結發送失敗：使用者帳號未輸入');
		
		$response = $this->_service->sendLink($account);
	
		#不管成功或失敗都是回到登入頁
		$msg = $response->msg;

		return redirect()->route('signin')->with('msg', $msg);
	}
	
	/* 
	 * @params: request
	 * @return: view
	 */
	public function showSetting($token)
	{
		$this->_service->getInfoByToken($token);
		$this->_viewModel->keepFormData($token); #account only
		
		return view('forget_password_setting')->with('viewModel', $this->_viewModel);
	}
	
	/* 登入驗證
	 * @params: request
	 * @return: view
	 */
	public function setting(Request $request)
	{
		$account 	= $request->input('account');
		$password	= $request->input('password');
		$captcha	= $request->input('captcha');
		
		$this->_viewModel->action = FormAction::SIGNIN;
		$this->_viewModel->keepFormData($account); #account only
		
		$validator = Validator::make($request->all(), [
            'account' => 'required|max:20',
			'password' => 'required|max:20',
        ]);
		
		$botSt = session()->get('botTimeValidate');
		
		if (! empty($captcha) OR $botSt->diffInSeconds(now()) < 1)
			 abort(400, 'Bad Request');
		
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