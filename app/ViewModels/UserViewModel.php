<?php

namespace App\ViewModels;

use App\Services\UserService;
use App\Enums\FormAction;
use App\Enums\Area;
use App\Enums\Operation;
use App\Enums\RoleGroup;

class UserViewModel
{
	private $_service;
	private $_title = '帳號管理';
	private $_data = [];
	
	public function __construct(UserService $userService)
	{
		$this->_service = $userService;
		
		#initialize
		$this->_data['action'] 		= NULL; #enum form action
		$this->_data['status']		= FALSE;
		$this->_data['msg'] 		= '';
		
		#Form Data
		$this->_data['userData'] 	= NULL; #DB data
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
		$this->_data['msg'] 	= '';
		
		$this->_setOptions();
	}
	
	/* Status / Msg
	 * @params: string
	 * @return: boolean
	 */
	public function success($msg = NULL)
	{
		$this->_data['status'] 	= TRUE;
		$this->_data['msg'] 	= $msg ?? '';
	}
	
	public function fail($msg)
	{
		$this->_data['status'] 	= FALSE;
		$this->_data['msg'] 	= $msg;
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
	
	/* Form所屬的參數選項
	 * @params:  
	 * @return: void
	 */
	private function _setOptions()
	{
		$this->_data['option']['area'] 		= Area::cases(); #enum
		$this->_data['option']['roleList']	= $this->_service->getRoleOptions();
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
		data_set($this->_data, 'userData.id', $id);
		data_set($this->_data, 'userData.ad', $adAccount);
		data_set($this->_data, 'userData.displayName', $displayName);
		data_set($this->_data, 'userData.roleId', $role);
	}
	
	/* Get user data
	 * @params: 
	 * @return: string
	 */
	public function getUserId()
    {
		return data_get($this->_data, 'userData.id', 0);
	}
	
	public function getUserAd()
	{
		return data_get($this->_data, 'userData.ad', '');
	}
	
	public function getUserDisplayName()
	{
		return data_get($this->_data, 'userData.displayName', '');
	}
	
	public function getUserRoleId()
	{
		return data_get($this->_data, 'userData.roleId', 0);
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
	
	
	#Page operation permission
	#判別登入使用者權限
	/* Delete permission
	 * @params: 
	 * @return: boolean
	 */
	public function canQuery()
	{
		return $this->_service->hasOperationPermission($this->_service->getFunctionCode(), Operation::READ->value);
	}
	
	public function canCreate()
	{
		return $this->_service->hasOperationPermission($this->_service->getFunctionCode(), Operation::CREATE->value);
	}
	
	public function canUpdate()
	{
		return $this->_service->hasOperationPermission($this->_service->getFunctionCode(), Operation::UPDATE->value);
	}
	
	public function canDelete()
	{
		return $this->_service->hasOperationPermission($this->_service->getFunctionCode(), Operation::DELETE->value);
	}
	
	/* Form Style */
	public function getBreadcrumb()
    {
		return $this->_title . ' | ' . $this->action->label();
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
		$userRoleId = $this->getUserRoleId();
		
		return ($roleId == $userRoleId);
	}
	
	/* supervisor permission
	 * @params: int : 欲刪除的user id(排除supervisor)
	 * @return: boolean
	 */
	public function disabledSupervisor($deleteRoleGroup)
	{
		return (RoleGroup::SUPERVISOR->value == $deleteRoleGroup) ? 'disabled' : '';
	}
}