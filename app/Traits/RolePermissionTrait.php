<?php

namespace App\Traits;

use App\Libraries\ResponseLib;
use Illuminate\Support\Str;

#Role Form : 身份表單功能使用
trait RolePermissionTrait
{
	/* 建立Permission設定For DB
	 * @params: array
	 * @return: array
	 */
	public function buildPermissionByFunction($settingList)
	{
		$permissions = [];
		
		if (empty($settingList))
			return $permissions;
		
		foreach($settingList as $groupCode => $groupList)
		{
			foreach($groupList as $actionCode => $operationList)
			{
				$permissions[] = $this->_createPermissionCode($groupCode, $actionCode, $operationList);
			}
		}
		
		return $permissions;
	}
	
	/* Create and Group by function
	 * @params: string
	 * @params: string
	 * @params: array
	 * @return: string
	 */
	private function _createPermissionCode($hexGroupCode, $hexActionCode, $hexOperationList)
	{
		$permission = 0;
		$decGroupCode = hexdec($hexGroupCode) << 24;
		$decActionCode = hexdec($hexActionCode) << 16;
		
		foreach($hexOperationList as $hexOperationCode)
		{
			$decOperationCode = hexdec($hexOperationCode);
			$permission = $permission | $decGroupCode | $decActionCode | $decOperationCode;
		}
		
		return Str::padLeft(dechex($permission), 8, '0');
	}
	
}