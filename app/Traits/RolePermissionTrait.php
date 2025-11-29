<?php

namespace App\Traits;

use App\Libraries\ResponseLib;
use Illuminate\Support\Str;

trait RolePermissionTrait
{
	/* 建立Permission設定For DB
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
	
	/* Auth Group Permission
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	public function hasGroupPermission($hexGroup, $hexUserPermissions)
	{
		$hexAction = '00';
		$hexOperation = '0000';
		
		return $this->_authPermission($hexGroup, $hexAction, $hexOperation, $hexUserPermissions);
	}
	
	/* Auth Function Permission
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	public function hasActionPermission($hexGroup, $hexAction, $hexUserPermissions)
	{
		$hexOperation = '0000';
		
		return $this->_authPermission($hexGroup, $hexAction, $hexOperation, $hexUserPermissions);
	}
	
	/* Auth CRUD Permission
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	public function hasOperationPermission($hexGroup, $hexAction, $hexOperation, $hexUserPermissions)
	{
		return $this->_authPermission($hexGroup, $hexAction, $hexOperation, $hexUserPermissions);
	}
	
	/* Auth Permission - Group/Action/Operation共用邏輯
	 * @params: string
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	private function _authPermission($hexGroupe, $hexAction, $hexOperation, $hexUserPermissions)
	{
		$decGroup = hexdec($hexGroupe) << 24;
		$decAction = hexdec($hexAction) << 16;
		$decOperation = hexdec($hexOperation);
		$decMask = $decGroup | $decAction | $decOperation;
		
		#$authPermission格式為DB內容
		foreach($hexUserPermissions as $permission)
		{
			$decPermission = hexdec($permission);
			
			#有一個符合即可
			if (($decMask & $decPermission) == $decMask)
				return TRUE;
		}
		
		return FALSE;
	}
}