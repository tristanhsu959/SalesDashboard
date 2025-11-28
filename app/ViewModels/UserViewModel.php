<?php

namespace App\ViewModels;

use App\Enums\FormAction;

class UserViewModel
{
	private $title = '帳號管理';
	private $data = [];
	
	public function __construct()
	{
		#initialize
		$this->data['action'] 	= NULL; #enum form action
		$this->data['userId']	= 0; #form create or update role id
		$this->data['status']	= FALSE;
		$this->data['msg'] 		= '';
		$this->data['roleData'] = NULL; #DB data
		$this->data['list'] 	= []; #DB data
	}
	
	/* initialize
	 * @params: enum
	 * @params: int
	 * @return: boolean
	 */
	public function initialize($action , $roleId = 0)
	{
		#初始化各參數及Form Options
		$this->data['action']	= $action;
		$this->data['userId']	= $roleId;
		$this->data['msg'] 		= '';
		
		if ($action == FormAction::CREATE OR $action == FormAction::UPDATE)
		{
			$this->data['Area'] 	= RoleGroup::cases();
			$this->data['functionList']	= $this->getAllMenu();
		}
	}
	
	/* Keep user form data
	 * @params: 
	 * @return: string
	 */
	public function keepFormData($name, $group, $settingList)
    {
		data_set($this->data, 'roleData.RoleName', $name);
		data_set($this->data, 'roleData.RoleGroup', $group);
		data_set($this->data, 'roleData.Permission', $this->buildPermissionByFunction($settingList));
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
	public function getUpdateRoleId()
    {
		return data_get($this->data, 'roleId', 0);
	}
	
	/* Role Data
	 * @params: 
	 * @return: string
	 */
	public function getRoleId()
    {
		return data_get($this->data, 'roleData.RoleId', 0);
	}
	
	public function getRoleName()
	{
		return data_get($this->data, 'roleData.RoleName', '');
	}
	
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
			FormAction::CREATE => route('role.create.post'),
			FormAction::UPDATE => route('role.update.post'),
		};
	}
	
	/* Form Style */
	public function selectedRoleGroup($group)
	{
		$group = intval($group);
		
		return ($group == data_get($this->data, 'roleData.RoleGroup', 0)) ? 'selected' : '';
	}
	
	public function checkedOperation($hexGroupCode, $hexActionCode, $hexOperation)
	{
		$authPermission = data_get($this->data, 'roleData.Permission', []);
		
		if ($this->authFunctionPermission($hexGroupCode, $hexActionCode, $hexOperation, $authPermission))
			return 'checked';
		
		return '';
	}
}