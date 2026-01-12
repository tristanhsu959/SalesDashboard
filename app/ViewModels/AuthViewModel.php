<?php

namespace App\ViewModels;

use App\Enums\FormAction;
use App\ViewModels\Attributes\attrStatus;

class AuthViewModel
{
	use attrStatus;
	
	private $_title 	= '登入';
	private $_data = [];
	
	public function __construct()
	{
		#Default data
		$this->_data['action'] 	= FormAction::SIGNIN; 
		$this->success();	
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
	
	/* Keep signin form data : account only, 以防會使用到
	 * @params: string
	 * @return: void
	 */
	public function keepFormData($account)
    {
		data_set($this->_data, 'account', $account);
	}
}