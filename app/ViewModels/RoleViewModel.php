<?php

namespace App\ViewModels;

use App\Services\RoleService;
use App\Traits\AuthorizationTrait;
use App\Traits\RolePermissionTrait;
use App\Enums\FormAction;
use App\Enums\RoleGroup;
use App\Enums\Operation;

class RoleViewModel
{
	use AuthorizationTrait, RolePermissionTrait;
	
	private $_service;
	private $_title = '身份管理';
	private $_data = [];
	
	public function __construct(RoleService $roleService)
	{
		$this->_service = $roleService;
		#initialize
		$this->_data['action'] 	= NULL; #enum form action
		$this->_data['status']	= FALSE;
		$this->_data['msg'] 	= '';
		
		#form data
		$this->_data['roleId']		= 0; #form create or update role id
		$this->_data['roleData']	= NULL; #DB _data
		$this->_data['list'] 		= []; #DB data
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
	
	/* Status / Msg
	 * @params: 
	 * @return: boolean
	 */
	public function success($msg = NULL)
	{
		$this->_data['status'] = TRUE;
		$this->_data['msg'] = $msg ?? '';
	}
	
	public function fail($msg)
	{
		$this->_data['status'] 	= FALSE;
		$this->_data['msg'] 		= $msg;
	}
	
	/* initialize
	 * @params: enum
	 * @params: int
	 * @return: boolean
	 */
	public function initialize($action , $roleId = 0)
	{
		#初始化各參數及Form Options
		$this->_data['action']	= $action;
		$this->_data['roleId']	= $roleId;
		$this->_data['msg'] 		= '';
		
		$this->_setOptions();
		$this->_data['operations'] = $this->_service->getOperationPermission();
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
	
	/* Form所屬的參數選項
	 * @params: enum
	 * @params: array
	 * @return: void
	 */
	private function _setOptions()
	{
		$this->_data['roleGroup'] 	= RoleGroup::cases();
		$this->_data['functionList']	= $this->getMenuFromConfig();
	}
	
	/* Keep user form data
	 * @params: 
	 * @return: string
	 */
	public function keepFormData($name, $group, $settingList)
    {
		data_set($this->_data, 'roleData.RoleName', $name);
		data_set($this->_data, 'roleData.RoleGroup', $group);
		data_set($this->_data, 'roleData.Permission', $this->buildPermissionByFunction($settingList));
	}
	
	
	/* Create or Update Role Id 
	 * @params: 
	 * @return: string
	 */
	public function getUpdateRoleId()
    {
		return data_get($this->_data, 'roleId', 0);
	}
	
	/* Role Data
	 * @params: 
	 * @return: string
	 */
	public function getRoleId()
    {
		return data_get($this->_data, 'roleData.RoleId', 0);
	}
	
	public function getRoleName()
	{
		return data_get($this->_data, 'roleData.RoleName', '');
	}
	
	#Page operation permission
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
	
	public function canDelete()
	{
		return in_array(Operation::DELETE->name, $this->_data['operations']);
	}
	
	
	/* Form Style */
	public function getBreadcrumb()
    {
		return $this->_title . ' | ' . $this->action->label();
	}
	
	public function selectedRoleGroup($group)
	{
		$group = intval($group);
		
		return ($group == data_get($this->_data, 'roleData.RoleGroup', 0));
	}
	
	public function checkedOperation($hexGroupCode, $hexActionCode, $hexOperation)
	{
		$authPermission = data_get($this->_data, 'roleData.Permission', []);
		
		return ($this->hasOperationPermission($hexGroupCode, $hexActionCode, $hexOperation, $authPermission));
	}
}