<?php
#目前未用到
namespace App\Services;

use App\Traits\AuthorizationTrait;
use App\Traits\RolePermissionTrait;
use Illuminate\Support\Arr;

class AppService
{
	#use AuthorizationTrait, RolePermissionTrait;
	
	public function __construct()
	{
		// $this->_repository = $partyRepository;
	}
	
	/* 取All Menu List (權限設定用)
	 * @params: 
	 * @return: array
	 */
	// public function getMenu()
	// {
		// return $this->getMenuFromConfig();
	// }
	
	/* 取有授權的Menu (登入驗後)
	 * @params: 
	 * @return: array
	 */
	// public function getAuthorizeMenu()
	// {
		// return $this->getMenuByPermission();
	// }
	
	
}
