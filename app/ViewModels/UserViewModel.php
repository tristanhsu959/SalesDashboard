<?php

namespace App\ViewModels;

use App\Services\UserService;
use App\Enums\FormAction;
use App\Enums\Area;
use App\Enums\RoleGroup;
use App\Enums\Functions;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use Illuminate\Support\Fluent;

class UserViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct(protected UserService $_service)
	{
		$this->function		= Functions::USER;
		$this->action 		= FormAction::LIST; 
		$this->backRoute 	= 'users';
		$this->success();
	}
	
	/* initialize
	 * @params: enum
	 * @return: void
	 */
	public function initialize($action)
	{
		#初始化各參數及Form Options
		$this->action	= $action;
		$this->success();
		
		$this->_setOptions();
	}
	
	/* Form所屬的參數選項
	 * @params:  
	 * @return: void
	 */
	private function _setOptions()
	{
		$this->set('options.roleList', $this->_service->getRoleOptions());
		$this->set('options.areas', Area::cases()); 
	}
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: string
	 */
	public function keepSearchData($adAccount = '', $displayName = '', $roleId = 0)
    {
		$this->set('search.ad', $adAccount);
		$this->set('search.name', $displayName);
		$this->set('search.roleId', $roleId);
	}
	
	/* Form submit action
	 * @params: 
	 * @return: string
	 */
	public function getFormAction() : string
    {
		return match($this->action)
		{
			FormAction::CREATE => route('user.create.post'),
			FormAction::UPDATE => route('user.update.post'),
		};
	}
	
	/* Keep user form data
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: void
	 */
	public function keepFormData($id = 0, $adAccount = '', $displayName = '', $roleId = 0, $updateAt = '')
    {
		$this->set('formData.id', $id);
		$this->set('formData.ad', $adAccount);
		$this->set('formData.name', $displayName);
		$this->set('formData.roleId', $roleId);
		$this->set('formData.updateAt', $updateAt);
	}
	/* User Data End */
	
	/* 判別列表Role是否可編或可刪
	 * @params: 
	 * @return: boolean
	 */
	public function canUpdateThisUser($thisRoleGroup)
	{
		return ! ($thisRoleGroup == RoleGroup::SUPERVISOR->value);
	}
	
	public function canDeleteThisUser($thisRoleGroup)
	{
		return ! ($thisRoleGroup == RoleGroup::SUPERVISOR->value);
	}
	
}