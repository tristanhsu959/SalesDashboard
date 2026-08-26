<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\ViewModels\AuthViewModel;
use App\Libraries\ResponseLib;
use App\Enums\FormAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
class AuthController extends Controller
{
	public function __construct(protected AuthService $_service, protected AuthViewModel $_viewModel)
	{
	}
	
	public function showSignin()
	{
		 $date = date("YmdH");

    $signature = base64_encode(hash_hmac('sha1', "bafang:$date", 'yKfuHq8vX4CnLAV51qyXdfp4989oYDHo', true));
	dd($signature);
		 // 1. 還原 C# 匿名物件的欄位順序與名稱 (大小寫必須與 C# 變數名一致)
        // 🚨 注意：C# 程式碼中是 _account.Id, password, _account.CreatedAt
        
        // 處理 C# 的 DateTime 格式 (ISO 8601，含微秒/毫秒與時區)
        // 如果舊系統是 UtcNow，請確保時區正確。這裡假設使用 ISO8601 格式
        $createdAt = Carbon::parse('2026-05-08 01:05:18.437');
        $createdAtFormatted = $createdAt->format("Y-m-d\TH:i:s.v");
		
		$payload = [			
            'Id'        => 12482,      
            'password'  => 'Info000111',     
            'CreatedAt' => $createdAtFormatted, 
        ];
		
		$jsonContent = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		
        // 3. 進行 SHA256 雜湊並轉為 Base64
        // raw_output 設為 true (等於 C# 的 byte陣列)，再透過 base64_encode 輸出
        $calculatedHash = base64_encode(hash('sha256', $jsonContent, true));
		
		#It is work******
		dd($calculatedHash, 'UuYbGJa+TSHkn54TSxHQ8ZFL9Ecum3q+LHinva2gAIE=');
		
		// 4. 安全地比對兩個字串是否相同
        dd( $calculatedHash, hash_equals('UuYbGJa+TSHkn54TSxHQ8ZFL9Ecum3q+LHinva2gAIE=', $calculatedHash));
	}
	/* Signin view
	 * @params: request
	 * @return: view
	 */
	public function showSignin1()
	{
		#自動登出,避免view載入錯誤
		$this->_service->signout();
		$this->_viewModel->action = FormAction::SIGNIN;
		session()->put('botTimeValidate', now());
		
		return view('signin')->with('viewModel', $this->_viewModel);
	}
	
	/* 登入驗證
	 * @params: request
	 * @return: view
	 */
	public function signin(Request $request)
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