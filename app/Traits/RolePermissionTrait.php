<?php

namespace App\Traits;

use App\Libraries\ResponseLib;
use Illuminate\Support\Str;

trait RolePermissionTrait
{
	/* 建立Permission設定 by list
	 * @params: array
	 * @return: array
	 */
	public function buildPermissionByFunction($settingList)
	{
		$permissions = [];
		
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
	
	/* Auth Permission
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: string
	 */
	public function authFunctionPermission($hexGroupCode, $hexActionCode, $hexOperationCode, $hexAuthPermissions)
	{
		$decGroupCode = hexdec($hexGroupCode) << 24;
		$decActionCode = hexdec($hexActionCode) << 16;
		$decOperationCode = hexdec($hexOperationCode);
		$decPermission = $decGroupCode | $decActionCode | $decOperationCode;
		
		#$authPermission格式為DB內容
		foreach($hexAuthPermissions as $authPermission)
		{
			$decAuthPermission = hexdec($authPermission);
			
			#有一個符合即可
			if (($decPermission & $decAuthPermission) == $decPermission)
				return TRUE;
		}
		
		return FALSE;
	}
}