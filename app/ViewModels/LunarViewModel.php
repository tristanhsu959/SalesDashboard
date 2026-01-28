<?php

namespace App\ViewModels;

use App\ViewModels\Attributes\attrStatus;

class LunarViewModel
{
	use attrStatus;
	
	private $_data = [];
	
	public function __construct()
	{
		data_set($this->_data, 'settings.tp', []);
		data_set($this->_data, 'settings.ts', []);
		$this->fail('');	
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
}