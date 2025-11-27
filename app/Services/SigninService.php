<?php

namespace App\Services;

use App\Libraries\ResponseLib;
use App\Traits\AuthenticationTrait;
use App\Traits\AuthorizationTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Exception;


class SigninService
{
	use AuthenticationTrait, AuthorizationTrait;
	
	private $_title = '登入驗證';
	
	public function __construct()
	{
		
	}
	
	/* 登入驗證(由trait決定驗證方式)
	 * @params: string
	 * @params: string
	 * @return: array
	 */
	public function authUser($account, $password)
	{
		$adInfo = $this->authAD($account, $password);
		
		if ($adInfo === FALSE)
			return FALSE;
		
		#Save to session
		$this->saveAuthUser($adInfo);
		
		return TRUE;
	}
}
