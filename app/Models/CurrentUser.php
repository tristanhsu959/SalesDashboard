<?php

namespace App\Models;

use App\Enums\RoleGroup;
use Illuminate\Support\Fluent;

class CurrentUser extends Fluent
{
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
		$this->fill($info);
	}
	
	/* 內建Supervisor (RoleGroup)
	 * @params:  
	 * @return: boolean
	 */
	public function isSupervisor()
	{
		$roleGroup = $this->get('roleGroup', 0);
		
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
		
		$permissions	= $this->get('rolePermission', []);
		$allowFunctions	= array_keys($permissions); #Key same as code
		
		return in_array($functionKey, $allowFunctions);
	}
	
	#新版會廢除,權限只控一層
	/* Auth permission of CRUD by current user
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	public function hasActionPermission($functionKey, $actionKey)
	{
		if ($this->isSupervisor())
			return TRUE;
		
		$permissions	= $this->get('rolePermission', []);
		$allowActions	= data_get($permissions, $functionKey, []); #array
		
		return in_array($actionKey, $allowActions);
	}
}