<?php

namespace App\ViewModels\Attributes;

use App\Models\CurrentUser;
use App\Traits\AuthTrait;
use App\Enums\Permission;

#Status & Message
trait attrAllowAction
{
	use AuthTrait;
	
	public function isCurrentUser($userId)
	{
		$currentUser = $this->getCurrentUser();
		
		return ($currentUser->userId == $userId);
	}
	
	/* Action permission
	 * @params: string
	 * @return: void
	 */
	public function canQuery()
	{
		$currentUser = $this->getCurrentUser();
		return $currentUser->hasActionPermission($this->_function, Permission::READ->value);
	}
	
	public function canCreate()
	{
		$currentUser = $this->getCurrentUser();
		return $currentUser->hasActionPermission($this->_function, Permission::CREATE->value);
	}
	
	public function canUpdate()
	{
		$currentUser = $this->getCurrentUser();
		return $currentUser->hasActionPermission($this->_function, Permission::UPDATE->value);
	}
	
	public function canDelete()
	{
		$currentUser = $this->getCurrentUser();
		return $currentUser->hasActionPermission($this->_function, Permission::DELETE->value);
	}
}