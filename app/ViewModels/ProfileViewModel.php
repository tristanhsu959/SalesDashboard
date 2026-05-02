<?php

namespace App\ViewModels;

use App\Services\UserService;
use App\Facades\AppManager;
use App\Enums\FormAction;
use App\Enums\Area;
use App\Enums\RoleGroup;
use App\Enums\Functions;
use App\ViewModels\Attributes\attrStatus;
use Illuminate\Support\Fluent;

class ProfileViewModel extends Fluent
{
	use attrStatus;
	
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
		$this->set('options.functions', AppManager::getMenu());
		$this->set('options.areas', Area::mapWithKeys());
		$this->set('options.supervisorGroupId',RoleGroup::SUPERVISOR->value); 
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
	public function keepFormData($id = 0, $account = '',  $password = '', $displayName = '', $department = '', $email = '', $isActive = TRUE, 
									$permission = [], $area = [], $updateAt = '', $hasSetPassword = FALSE)
    {
		$this->set('formData.id', $id);
		$this->set('formData.account', $account);
		$this->set('formData.password', $password);
		$this->set('formData.displayName', $displayName);
		$this->set('formData.department', $department);
		$this->set('formData.email', $email);
		$this->set('formData.isActive', $isActive);
		$this->set('formData.permission', $permission);
		$this->set('formData.area', $area);
		$this->set('formData.updateAt', $updateAt);
		$this->set('formData.hasSetPassword', $hasSetPassword);
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