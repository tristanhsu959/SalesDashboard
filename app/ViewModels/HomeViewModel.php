<?php

namespace App\ViewModels;

use App\Enums\FormAction;
use App\Enums\Functions;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;

class HomeViewModel
{
	use attrStatus, attrActionBar;
	
	private $_function 	= Functions::HOME;
	private $_backRoute	= 'home'; #set by route name
	private $_data 		= [];
	
	public function __construct()
	{
		#Base data
		$this->_data['action'] = FormAction::HOME; #enum form action
		$this->success();	#default
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
	
	/* breadcrumb
	 * @params: 
	 * @return: array
	 */
	public function breadcrumb()
	{
		return $this->getBreadcrumbByDefault();
	}
}