<?php

namespace App\Traits;

use App\Libraries\ResponseLib;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

trait MenuTrait
{
	
	/* 取All Menu List (權限設定用)
	 * @params: 
	 * @return: array
	 */
	public function getMenu()
	{
		return config('web.menu');
	}
	
	/* 取有授權的Menu List (登入驗後)
	 * @params: 
	 * @return: array
	 */
	public function getAuthorizeMenu()
	{
		#要補權限驗證
		return config('web.menu');
	}
}