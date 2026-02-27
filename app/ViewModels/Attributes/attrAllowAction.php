<?php

namespace App\ViewModels\Attributes;

use App\Facades\AppManager;
use App\Models\CurrentUser;
use App\Traits\AuthTrait;
use App\Enums\Operation;

#Status & Message
trait attrAllowAction
{
	public function isCurrentUser($userId)
	{
		$currentUser = AppManager::getCurrentUser();
		
		return ($currentUser->userId == $userId);
	}
	
	/* Action permission
	 * @params: string
	 * @return: void
	 */
	public function hasPermission()
	{
		#改為只有判別function
		$currentUser = AppManager::getCurrentUser();
		return $currentUser->hasPermissionTo($this->function->value);
	}
	
	/* Action permission
	 * @params: string
	 * @return: void
	 */
	public function canQuery()
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
	}
}