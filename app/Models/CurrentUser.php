<?php

namespace App\Models;

use App\Enums\RoleGroup;

class CurrentUser
{
	private $_data = [];
	
	/*
	[
		"company" => "八方雲集國際股份有限公司"
		"department" => "資訊處"
		"title" => "經理"
		"displayName" => "Tristan Hsu 許方毓"
		"employeeId" => "T2025098"
		"name" => "許方毓"
		"mail" => "tristan.hsu@8way.com.tw"
		"userId" => 1
		"userAd" => "tristan.hsu"
		"userRoleId" => 1
		"roleGroup" => 1
		"rolePermission" => array:7 [▶]
		"roleArea" => array:6 [▶]
	]
	*/
  
	public function __construct($adInfo, $userInfo)
	{
		$info = array_merge($adInfo, $userInfo);
		$this->_data = $info;
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
	
	/* 內建Supervisor (RoleGroup)
	 * @params:  
	 * @return: boolean
	 */
	public function isSupervisor()
	{
		$roleGroup = data_get($this->_data, 'roleGroup', 0);
		
		return ($roleGroup == RoleGroup::SUPERVISOR->value);
	}
	
	/* Auth permission of function by current user
	 * @params: string
	 * @return: boolean
	 */
	public function hasFunctionPermission($functionKey)
	{
		if ($this->isSupervisor())
			return TRUE;
		
		$permissions	= data_get($this->_data, 'rolePermission', []);
		$allowFunctions	= array_keys($permissions); #Key same as code
		
		return in_array($functionKey, $allowFunctions);
	}
	
	/* Auth permission of CRUD by current user
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	public function hasActionPermission($functionKey, $actionKey)
	{
		if ($this->isSupervisor())
			return TRUE;
		
		$permissions	= data_get($this->_data, 'rolePermission', []);
		$allowActions	= data_get($permissions, $functionKey, []); #array
		
		return in_array($actionKey, $allowActions);
	}
}