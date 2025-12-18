<?php

namespace App\ViewModels;

use App\Services\RoleService;
use App\Libraries\MenuLib;
use App\Enums\FormAction;
use App\Enums\RoleGroup;
use App\Enums\Operation;
use App\Enums\Area;

class RoleViewModel
{
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
		$this->_data['roleData']	= NULL; #For detail view form data
		$this->_data['list'] 		= []; #For list view
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
	
	/* initialize
	 * @params: enum
	 * @params: int
	 * @return: boolean
	 */
	public function initialize($action)
	{
		#初始化各參數及Form Options
		$this->_data['action']	= $action;
		$this->_data['msg'] 	= '';
		
		if ($action != FormAction::List)
			$this->_setOptions();
	}
	
	/* Form所屬的參數選項
	 * @params: enum
	 * @params: array
	 * @return: void
	 */
	private function _setOptions()
	{
		$this->_data['option']['roleGroupList'] = RoleGroup::getEnabledList();
		$this->_data['option']['functionList']	= MenuLib::all();
		$this->_data['option']['areaList'] 		= Area::cases(); #enum
	}
	
	/* Form submit action for edit
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
	
	/* Keep user form data
	 * @params: 
	 * @return: string
	 */
	public function keepFormData($roldId = 0, $name = '', $group = 0, $permission = [], $area = [])
    {
		#todo area
		data_set($this->_data, 'roleData.id', $roldId);
		data_set($this->_data, 'roleData.name', $name);
		data_set($this->_data, 'roleData.group', $group);
		data_set($this->_data, 'roleData.permission', $permission);
		data_set($this->_data, 'roleData.area', $area);
	}
	
	/* Role Data
	 * @params: 
	 * @return: string
	 */
	public function getRoleId()
    {
		return data_get($this->_data, 'roleData.id', 0);
	}
	public function getRoleName()
	{
		return data_get($this->_data, 'roleData.name', '');
	}
	public function getRoleGroup()
	{
		return data_get($this->_data, 'roleData.group', 0);
	}
	public function getRolePermission()
	{
		return data_get($this->_data, 'roleData.permission', []);
	}
	public function getRoleArea()
	{
		return data_get($this->_data, 'roleData.area', []);
	}
	 
	#Page operation permission
	#內建身份判別
	public function isSupervisorGroup($roleGroup)
	{
		return ($roleGroup == RoleGroup::SUPERVISOR->value);
	}
	
	/* 指登入使用者的功能CRUD權限(#call from Authorization trait)
	 * @params: 
	 * @return: string
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
	
	/* Selected option */
	public function selectedRoleGroup($group)
	{
		$group = intval($group);
		
		return ($group == $this->getRoleGroup());
	}
	
	/* Form setting => 是依據新增或編輯的User
	 * @params: 
	 * @return: boolean
	 */
	public function checkedOperation($functionCode, $operationValue)
	{
		$permissionSetting 	= $this->getRolePermission(); 
		$operationSetting	= data_get($permissionSetting, $functionCode, []);
		
		return in_array($operationValue, $operationSetting);
	}
	
	/* Area checked prop
	 * @params: 
	 * @return: boolean
	 */
	public function checkedArea($areaValue)
	{
		$areaSetting 	= $this->getRoleArea(); 
		
		return in_array($areaValue, $areaSetting);
	}
}