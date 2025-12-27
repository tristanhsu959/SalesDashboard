<?php

namespace App\Services\Modules;

use App\Enums\RoleGroup;

class CurrentUser
{
	private $_data;
    /**
     * Sigin user
     */
    public function __construct($adInfo, $userInfo)
    {
        $this->_data = array_merge($adInfo, $userInfo); 
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
		return (data_get($this->_data, 'roleGroup', 0) == RoleGroup::SUPERVISOR->value);
	}
	
	/* Auth permission of function by current user
	 * @params: string
	 * @return: boolean
	 */
	public function hasFunctionPermission($functionCode)
	{
		if ($this->isSupervisor())
			return TRUE;
		
		$userPermission		= data_get($this->_data, 'permission', []);
		$functionCodeList	= array_keys($userPermission); #Key same as code
		
		return in_array($functionCode, $functionCodeList);
	}
	
	
	
	
	
	
	
	
	/* Get current user permissions
	 * @params: 
	 * @return: array
	 */
	public function getSigninUserPermission()
	{
		$signinUser = $this->getSigninUserInfo();
		
		if (empty($signinUser))
			return [];
		
		$userPermission = $signinUser['permission'];
		
		return $userPermission;
	}
	
	
	
	
	
	
	
	/* Auth permission of CRUD by current user
	 * @params: string
	 * @params: string
	 * @return: boolean
	 */
	public function hasOperationPermission($functionCode, $operationValue)
	{
		if ($this->isSupervisor())
			return TRUE;
		
		$userPermission = $this->getSigninUserPermission();
		$userOperations = data_get($userPermission, $functionCode, []); #array
		
		return in_array($operationValue, $userOperations);
	}
	
}
