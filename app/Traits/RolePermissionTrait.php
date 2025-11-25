<?php

namespace App\Traits;

use App\Libraries\ResponseLib;
use Illuminate\Support\Str;

trait RolePermissionTrait
{
	/* 建立Permission設定
	 * @params: 
	 * @return: array
	 */
	public function getPermissions($settingList)
	{
		$permissions = [];
		
		foreach($settingList as $groupCode => $groupList)
		{
			foreach($groupList as $actionCode => $operationList)
			{
				$permissions[] = $this->_buildPermissions($groupCode, $actionCode, $operationList);
			}
		}
		
		return $permissions;
	}
	
	/* Create and Group by function
	 * @params: 
	 * @return: array
	 */
	private function _buildPermissions($groupCode, $actionCode, $operationList)
	{
		$permission = 0;
		
		foreach($operationList as $operationCode)
		{
			$hexString = "{$groupCode}{$actionCode}{$operationCode}";
			$permission = $permission | hexdec($hexString);
		}
		
		return dechex($permission);
	}
}