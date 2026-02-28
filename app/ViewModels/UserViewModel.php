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
	
	/* Get search data
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: string
	 */
	public function getSearchAd()
	{
		return data_get($this->_data, 'search.userAd', '');
	}
	
	public function getSearchName()
	{
		return data_get($this->_data, 'search.userDisplayName', '');
	}
	
	public function getSearchArea()
	{
		return data_get($this->_data, 'search.userAreaId', 0);
	}
	
	/* Keep user form data
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: void
	 */
	public function keepFormData($id = 0, $adAccount = '', $displayName = '', $roleId = 0)
    {
		$this->set('formData.id', $id);
		$this->set('formData.ad', $adAccount);
		$this->set('formData.displayName', $displayName);
		$this->set('formData.roleId', $roleId);
	}
	/* User Data End */
	
	/* Get role name of list
	 * @params: int
	 * @return: string
	 */
	public function getRoleById($roleId)
	{
		$list = $this->_data['option']['roleList'];
		return data_get($list, $roleId, '');
	}
	
	/* Search form */
	public function selectedSearchArea($areaId)
	{
		return ($areaId == $this->getSearchArea());
	}
	
	/* User form */
	public function checkedArea($areaId)
	{
		$userAreaIds = $this->getUserAreaId();
		
		return in_array($areaId, $userAreaIds);
	}
	
	public function checkedRole($roleId)
	{
		return ($roleId == $this->_data['roleId']);
	}
	
	
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