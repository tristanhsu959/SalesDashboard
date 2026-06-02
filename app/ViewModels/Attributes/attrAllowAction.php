<?php

namespace App\ViewModels\Attributes;

use App\Facades\AppManager;
use App\Models\CurrentUser;
use App\Traits\AuthTrait;
use App\Enums\Operation;
use App\Enums\Area;

#Status & Message
trait attrAllowAction
{
	public function isCurrentUser($userId)
	{
		$currentUser = AppManager::getCurrentUser();
		
		return ($currentUser->id == $userId);
	}
	
	/* Action permission
	 * @params: string
	 * @return: void
	 */
	public function hasPermission()
	{
		#改為只有判別function
		#Middleware已有過濾, 可不用
		$currentUser = AppManager::getCurrentUser();
		return $currentUser->hasPermissionTo($this->function->value);
	}
	
	/* Area permission
	 * @params: string
	 * @return: void
	 */
	public function getAuthAreaList()
	{
		$currentUser = AppManager::getCurrentUser();
		$authAreaList = $currentUser['roleArea'];
		
		$allAreaList = Area::options();
		
		$list = collect($allAreaList)->filter(function($item, $key) use($authAreaList){
			return in_array($key, $authAreaList);
		})->toArray();
		
		return $list;
	}
	
	/* Action permission
	 * @params: string
	 * @return: void
	 */
	/* public function canQuery()
	{
		$currentUser = AppManager::getCurrentUser();
		return $currentUser->hasActionPermission($this->function->value, Operation::READ->value);
	}
	
	public function canCreate()
	{
		$currentUser = AppManager::getCurrentUser();
		return $currentUser->hasActionPermission($this->function->value, Operation::CREATE->value);
	}
	
	public function canUpdate()
	{
		$currentUser = AppManager::getCurrentUser();
		return $currentUser->hasActionPermission($this->function->value, Operation::UPDATE->value);
	}
	
	public function canDelete()
	{
		$currentUser = AppManager::getCurrentUser();
		return $currentUser->hasActionPermission($this->function->value, Operation::DELETE->value);
	} */
}