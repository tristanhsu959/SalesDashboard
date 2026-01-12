<?php

namespace App\ViewModels\Attributes;

use App\Enums\FormAction;

#Breadcrumb | backurl
trait attrActionBar
{
	/* Set status & msg
	 * @params: string
	 * @return: void
	 */
	public function breadcrumb()
	{
		$breadcrumb 	= [];
		$action 		= data_get($this->_data, 'action', '');
		
		$breadcrumb[] 	= $this->_title;
		$actionName 	= $action->label();
		
		if (empty($actionName))
			return $breadcrumb;
		
		$breadcrumb[] = $actionName;
		
		return $breadcrumb;
	}
	
	public function backRoute()
	{
		$action = data_get($this->_data, 'action', '');
		$except = [FormAction::SIGNIN->value, FormAction::HOME->value, FormAction::LIST->value];
		
		if (in_array($action->value, $except))
			return '';
		else
			return $this->_backRoute;
	}
}