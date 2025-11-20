<?php

namespace App\Traits;

use App\Libraries\ResponseLib;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

trait MenuTrait
{
	public function getAuthorizeMenu()
	{
		#要補權限驗證
		return config('web.menu');
	}
}