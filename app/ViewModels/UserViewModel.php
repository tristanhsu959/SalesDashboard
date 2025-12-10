<?php

namespace App\ViewModels;

use App\Services\UserService;
use App\Enums\FormAction;
use App\Enums\Area;
use App\Enums\Operation;

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
		$this->_data['userId']		= 0; #form create or update role id
		$this->_data['userData'] 	= NULL; #DB data
		$this->_data['list'] 		= []; #DB data
		$this->_data['search']		= [];
		$this->_data['operations'] 	= [];
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
	 * @params: int
	 * @return: void
	 */
	public function initialize($action , $userId = 0)
	{
		#初始化各參數及Form Options
		$this->_data['action']	= $action;
		$this->_data['userId']	= $userId;
		$this->_data['msg'] 	= '';
		
		$this->_setOptions();
		$this->_data['operations'] = $this->_service->getOperationPermission();
	}
	
	/* Status / Msg
	 * @params: 
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
	 * @return: 
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
	 * @params: enum
	 * @params: array
	 * @return: void
	 */
	private function _setOptions()
	{
		$this->_data['area'] 	= Area::cases(); #enum
		$this->_data['roleList']= $this->_service->getRoleOptions();
	}
	
	/* Keep user search data
	 * @params: 
	 * @return: string
	 */
	public function keepSearchData($adAccount, $displayName, $area)
    {
		data_set($this->_data, 'search.UserAd', $adAccount);
		data_set($this->_data, 'search.UserDisplayName', $displayName);
		data_set($this->_data, 'search.UserAreaId', $area);
	}
	
	public function getSearchAd()
	{
		return data_get($this->_data, 'search.UserAd', '');
	}
	
	public function getSearchName()
	{
		return data_get($this->_data, 'search.UserDisplayName', '');
	}
	
	public function getSearchArea()
	{
		return data_get($this->_data, 'search.UserAreaId', 0);
	}
	
	/* Keep user form data
	 * @params: 
	 * @return: string
	 */
	public function keepFormData($adAccount, $displayName, $area, $role)
    {
		data_set($this->_data, 'userData.UserAd', $adAccount);
		data_set($this->_data, 'userData.UserDisplayName', $displayName);
		data_set($this->_data, 'userData.UserRoleId', $role);
		data_set($this->_data, 'userData.UserAreaId', $area);
	}
	
	/* Create or Update Role Id 
	 * @params: 
	 * @return: string
	 */
	public function getUpdateUserId()
    {
		return data_get($this->_data, 'userId', 0);
	}
	
	/* User Data
	 * @params: 
	 * @return: string
	 */
	public function getUserId()
    {
		return data_get($this->_data, 'userData.UserId', 0);
	}
	
	public function getUserAd()
	{
		return data_get($this->_data, 'userData.UserAd', '');
	}
	
	public function getUserDisplayName()
	{
		return data_get($this->_data, 'userData.UserDisplayName', '');
	}
	
	public function getUserRoleId()
	{
		return data_get($this->_data, 'userData.UserRoleId', 0);
	}
	
	public function getUserAreaId()
	{
		return data_get($this->_data, 'userData.UserAreaId', []);
	}
	/* User Data End */
	
	/* List Data
	 * @params: 
	 * @return: string
	 */
	public function getRoleById($roleId)
	{
		$collect = collect($this->_data['roleList']);
		$collect = $collect->keyBy('RoleId')->toArray();
		
		return data_get($collect, "{$roleId}.RoleName", '');
	}
	
	
	#Page operation permission
	#判別登入使用者權限
	public function canQuery()
	{
		return in_array(Operation::READ->name, $this->_data['operations']);
	}
	
	public function canCreate()
	{
		return in_array(Operation::CREATE->name, $this->_data['operations']);
	}
	
	public function canUpdate()
	{
		return in_array(Operation::UPDATE->name, $this->_data['operations']);
	}
	
	/* Delete permission
	 * @params: int : 欲刪除的user id
	 * @return: boolean
	 */
	public function canDelete()
	{
		return in_array(Operation::DELETE->name, $this->_data['operations']);
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
	 * @params: int : 欲刪除的user id
	 * @return: boolean
	 */
	public function disabledSupervisor($deleteUserAd)
	{
		return $this->_service->isSupervisor($deleteUserAd) ? 'disabled' : '';
	}
}