<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Libraries\ResponseLib;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class OidcAuthController extends Controller
{
	public function __construct()
	{
	}
	
	/* Redirect to webcomm
	 * @params: request
	 * @return: view
	 */
	public function redirect()
	{
		return Socialite::driver('webcomm')->redirect();
	}
	
	/* Webcomm oidc auth callback
	 * @params: request
	 * @return: view
	 */
	public function callback()
	{
		try 
		{
			$oidcUser = Socialite::driver('webcomm')->stateless()->user();
			dd($oidcUser);
			 dd(request()->all()); 
			$email = $oidcUser->getEmail();
			$name = $oidcUser->getName() ?? $oidcUser->getNickname();

			if (empty($email)) 
				return "偉康未回傳 Email 資訊";
			
			// 不使用 Model，直接使用 DB Facade
			/* $user = DB::table('users')->where('email', $email)->first();

			if (!$user) {
				$userId = DB::table('users')->insertGetId([
					'name'       => $name,
					'email'      => $email,
					'password'   => bcrypt(Str::random(16)),
					'created_at' => now(),
					'updated_at' => now(),
				]);
				$user = DB::table('users')->where('id', $userId)->first();
			} */

			// 使用 ID 執行 Session 登入
			Auth::loginUsingId($user->id);

			return redirect()->intended('/dashboard');

		}
		catch (\Exception $e) 
		{
			return "偉康驗證失敗：" . $e->getMessage();
		} 
	}
}