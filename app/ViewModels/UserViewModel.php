<?php

namespace App\ViewModels;

use App\Services\UserService;
use App\Enums\FormAction;
use App\Enums\Area;

class UserViewModel
{
	private $_service;
	private $title = '帳號管理';
	private $data = [];
	
	public function __construct(UserService $userService)
	{
		$this->_service = $userService;
		#initialize
		$this->data['action'] 	= NULL; #enum form action
		$this->data['userId']	= 0; #form create or update role id
		$this->data['status']	= FALSE;
		$this->data['msg'] 		= '';
		$this->data['userData'] = NULL; #DB data
		$this->data['list'] 	= []; #DB data
	}
	
	public function __set($name, $value)
    {
		$this->data[$name] = $value;
    }
	
	public function __get($name)
    {
		return $this->data[$name];
	}
	
	/* 須有isset, 否則empty()會判別錯誤 */
	public function __isset($name)
    {
		return array_key_exists($name, $this->data);
	}
	
	/* initialize
	 * @params: enum
	 * @params: int
	 * @return: void
	 */
	public function initialize($action , $userId = 0)
	{
		#初始化各參數及Form Options
		$this->data['action']	= $action;
		$this->data['userId']	= $userId;
		$this->data['msg'] 		= '';
		
		$this->_setOptions();
	}
	
	/* Form所屬的參數選項
	 * @params: enum
	 * @params: array
	 * @return: void
	 */
	private function _setOptions()
	{
		$this->data['area'] 	= Area::cases();
		$this->data['roleList']	= $this->_service->getRoleOptions();
	}
	
	/* Keep user form data
	 * @params: 
	 * @return: string
	 */
	public function keepFormData($adAccount, $displayName, $area, $role)
    {
		data_set($this->data, 'userData.UserAd', $adAccount);
		data_set($this->data, 'userData.UserDisplayName', $displayName);
		data_set($this->data, 'userData.UserAreaId', $area);
		data_set($this->data, 'userData.UserRoleId', $role);
	}
	
	/* Status / Msg
	 * @params: 
	 * @return: boolean
	 */
	public function success($msg = NULL)
	{
		$this->data['status'] = TRUE;
		$this->data['msg'] = $msg ?? '';
	}
	
	public function fail($msg)
	{
		$this->data['status'] 	= FALSE;
		$this->data['msg'] 		= $msg;
	}
	
	/* Create or Update Role Id 
	 * @params: 
	 * @return: string
	 */
	public function getUpdateUserId()
    {
		return data_get($this->data, 'userId', 0);
	}
	
	/* User Data
	 * @params: 
	 * @return: string
	 */
	public function getUserId()
    {
		return data_get($this->data, 'userData.UserId', 0);
	}
	
	public function getUserAd()
	{
		return data_get($this->data, 'userData.UserAd', '');
	}
	
	public function getUserDisplayName()
	{
		return data_get($this->data, 'userData.UserDisplayName', '');
	}
	
	public function getUserAreaId()
	{
		return data_get($this->data, 'userData.UserAreaId', 0);
	}
	
	public function getUserRoleId()
	{
		return data_get($this->data, 'userData.UserRoleId', 0);
	}
	
	/* User Data End */
	
	
	public function getBreadcrumb()
    {
		return $this->title . ' | ' . $this->action->label();
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
	
	/* Form Style */
	public function selectedArea($areaId)
	{
		$userAreaId = $this->getUserAreaId();
		
		if ($areaId == $userAreaId)
			return 'selected';
		
		return '';
	}
	
	public function checkedRole($roleId)
	{
		$userRoleId = $this->getUserRoleId();
		
		if ($roleId == $userRoleId)
			return 'checked';
		
		return '';
	}
}