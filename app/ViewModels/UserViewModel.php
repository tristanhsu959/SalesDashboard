<?php

namespace App\ViewModels;

use App\Services\UserService;
use App\Enums\FormAction;
use App\Enums\Area;
use App\Enums\Operation;
use App\Enums\RoleGroup;
use App\Enums\Functions;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use App\Traits\AuthTrait;

class UserViewModel
{
	use AuthTrait;
	use attrStatus, attrActionBar, attrAllowAction;
	
	private $_function 	= Functions::USER;
	private $_backRoute	= 'user.list';
	private $_data = [];
	
	public function __construct(protected UserService $_service)
	{
		#initialize
		$this->_data['action'] 		= NULL; #enum form action
		$this->_data['status']		= FALSE;
		$this->_data['msg'] 		= '';
		
		#Form Data
		$this->_data['list'] 		= []; #DB data
		$this->_data['search']		= [];
		$this->_data['option']		= [];
	}
	
	public function __set($name, $value)
    {
		$this->_data[$name] = $value;
    }
	
	public function __get($name)
    {
		return data_get($this->_data, $name, '');
	}
	
	/* 須有isset, 否則empty()會判別錯誤 */
	public function __isset($name)
    {
		return array_key_exists($name, $this->_data);
	}
	
	/* initialize
	 * @params: enum
	 * @return: void
	 */
	public function initialize($action)
	{
		#初始化各參數及Form Options
		$this->_data['action']	= $action;
		$this->success();
		
		$this->_setOptions();
	}
	
	/* Form所屬的參數選項
	 * @params:  
	 * @return: void
	 */
	private function _setOptions()
	{
		$this->_data['option']['area'] 		= Area::cases(); #enum
		$this->_data['option']['roleList']	= $this->_service->getRoleOptions();
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
	
	/* Keep search data of form
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: string
	 */
	public function keepSearchData($adAccount, $displayName, $area)
    {
		data_set($this->_data, 'search.userAd', $adAccount);
		data_set($this->_data, 'search.userDisplayName', $displayName);
		data_set($this->_data, 'search.userAreaId', $area);
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
	public function keepFormData($id = 0, $adAccount = '', $displayName = '', $role = 0)
    {
		data_set($this->_data, 'id', $id);
		data_set($this->_data, 'ad', $adAccount);
		data_set($this->_data, 'displayName', $displayName);
		data_set($this->_data, 'roleId', $role);
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
	
	/* supervisor permission
	 * @params: int : 欲刪除的user id(排除supervisor)
	 * @return: boolean
	 */
	public function disabledSupervisor($deleteRoleGroup)
	{
		return (RoleGroup::SUPERVISOR->value == $deleteRoleGroup) ? 'disabled' : '';
	}
	
	/* 判別列表Role是否可編或可刪
	 * @params: 
	 * @return: boolean
	 */
	public function canUpdateThisUser($thisRoleGroup)
	{
		$currentUser = $this->getCurrentUser();
		
		#Supervisor才能編輯自己
		if (! $currentUser->isSupervisor() && $thisRoleGroup == RoleGroup::SUPERVISOR->value)
			return FALSE;
		else
			return TRUE;
	}
	
	public function canDeleteThisUser($thisRoleGroup)
	{
		return ! ($thisRoleGroup == RoleGroup::SUPERVISOR->value);
	}
}