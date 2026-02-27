<?php

namespace App\ViewModels;

use App\Libraries\MenuLib;
use App\Enums\FormAction;
use App\Enums\RoleGroup;
use App\Enums\Operation;
use App\Enums\Area;
use App\Enums\Functions;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use Illuminate\Support\Fluent;

class RoleViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct()
	{
		$this->function		= Functions::ROLE;
		$this->action 		= FormAction::LIST; 
		$this->backRoute 	= 'role.list';
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
		
		if ($action != FormAction::LIST)
			$this->_setOptions();
	}
	
	/* Form所屬的參數選項
	 * @params:  
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
	 * @return: string
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
	 * @params: int
	 * @params: string
	 * @params: int
	 * @params: array
	 * @params: array
	 * @return: void
	 */
	public function keepFormData($roldId = 0, $name = '', $group = 0, $permission = [], $area = [])
    {
		#todo area
		data_set($this->_data, 'id', $roldId);
		data_set($this->_data, 'name', $name);
		data_set($this->_data, 'group', $group);
		data_set($this->_data, 'permission', $permission);
		data_set($this->_data, 'area', $area);
	}
	
	/* Selected option */
	public function selectedRoleGroup($group)
	{
		$group = intval($group);
		
		return ($group == $this->_data['group']);
	}
	
	/* Area checked prop
	 * @params: 
	 * @return: boolean
	 */
	public function checkedArea($area)
	{
		return in_array($area, $this->_data['area']);
	}
	
	/* Form permission => 是依據新增或編輯的Role
	 * @params: 
	 * @return: boolean
	 */
	public function checkedOperation($functionKey, $operation)
	{
		$permissions	= $this->_data['permission']; 
		$operations		= data_get($permissions, $functionKey, []);
		
		return in_array($operation, $operations);
	}
	
	/* 判別列表Role是否可編或可刪
	 * @params: 
	 * @return: boolean
	 */
	public function canUpdateThisRole($roleGroup)
	{
		return (RoleGroup::SUPERVISOR->value == $roleGroup) ? FALSE : TRUE; #super visor can not edit
	}
	
	public function canDeleteThisRole($roleGroup)
	{
		return (RoleGroup::SUPERVISOR->value == $roleGroup) ? FALSE : TRUE; #super visor can not edit
	}
	
	/* breadcrumb
	 * @params: 
	 * @return: array
	 */
	/* public function breadcrumb()
	{
		return $this->getBreadcrumbByDefault();
	} */
}