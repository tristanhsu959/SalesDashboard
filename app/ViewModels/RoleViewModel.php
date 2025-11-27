<?php

namespace App\ViewModels;

use App\Enums\FormAction;
use App\Traits\RolePermissionTrait;

class RoleViewModel
{
	use RolePermissionTrait;
	
	#private $roleId = NULL;
	private $title = '身份管理';
	#private $action; #enum
	
	private $data = [];
	#private $status;
	#private $msg = '';
	
	#private $option; #其它參數
	
	public function __construct()
	{
		#initialize
		$this->data['status']	= FALSE;
		$this->data['msg'] 		= '';
		$this->data['action'] 	= NULL; #enum form action
		$this->data['roleData'] = NULL;
		$this->data['list'] 	= [];
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