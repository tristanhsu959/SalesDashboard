<?php

namespace App\ViewModels;

use App\Enums\FormAction;

class RoleViewModel
{
	private $roleId = NULL;
	private $title = '身份管理';
	private $action; #enum
	
	private $data = [];
	private $status;
	private $msg = '';
	
	private $option; #其它參數
	
	public function __set($name, $value)
    {
		if ($name == 'response')
			$this->_setResponse($value);
		else if (property_exists($this, $name))
			$this->$name = $value;
		else 
			$this->option[$name] = $value;
    }
	
	public function __get($name)
    {
		if (property_exists($this, $name))
			return $this->$name;
		else
			return $this->option[$name];
	}
	
	/* 須有isset, 否則empty()會判別錯誤 */
	public function __isset($name)
    {
		if (property_exists($this, $name))
			return isset($this->$name);
		else
			return array_key_exists($name, $this->option);
	}
	
	private function _setResponse($response)
	{
		$this->data 	= $response['data'];
		$this->status	= $response['status'];
		$this->msg 		= $response['msg'];
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
	public function getRoleName()
	{
		return $this->data->RoleName ?? '';
	}
	
	public function selectRoleGroup($group)
	{
		return ($group == $this->data->RoleGroup) ? 'selected' : '';
	}
}