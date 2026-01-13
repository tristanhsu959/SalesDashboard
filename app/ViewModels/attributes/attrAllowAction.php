<?php

namespace App\ViewModels\Attributes;

use App\Models\CurrentUser;
use App\Traits\AuthTrait;
use App\Enums\Operation;

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
		return $currentUser->hasActionPermission($this->_function, Operation::READ->value);
	}
	
	public function canCreate()
	{
		$currentUser = $this->getCurrentUser();
		return $currentUser->hasActionPermission($this->_function, Operation::CREATE->value);
	}
	
	public function canUpdate()
	{
		$currentUser = $this->getCurrentUser();
		return $currentUser->hasActionPermission($this->_function, Operation::UPDATE->value);
	}
	
	public function canDelete()
	{
		$currentUser = $this->getCurrentUser();
		return $currentUser->hasActionPermission($this->_function, Operation::DELETE->value);
	}
}