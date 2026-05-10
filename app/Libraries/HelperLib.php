<?php

namespace App\Libraries;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;

#Util helper
class HelperLib
{
	private $_response;
	
	public function __construct()
	{
	}
	
	/* Cache name
	 * @params: string
	 * @params: array
	 * @return: object
	 */
	public static function buildCacheKey(array $params)
	{
		$keys[] = $params;
		$keys = Arr::whereNotNull(Arr::flatten($keys));
		
		return implode(':', $keys);
	}
}