<?php

namespace App\ViewModels\Attributes;

use App\Enums\FormAction;

#Breadcrumb | backurl
trait attrActionBar
{
	public function isHome()
	{
		$action = data_get($this->_data, 'action', NULL);
		
		if (empty($action))
			return FALSE;
		else
			return ($action == FormAction::HOME);
	}
	
	/* Set status & msg
	 * @params: string
	 * @return: void
	 */
	public function breadcrumb()
	{
		$breadcrumb 	= [];
		$function		= $this->_function;
		$action 		= data_get($this->_data, 'action', '');
		$byAction		= isset($this->_breadcrumbByAction) ? $this->_breadcrumbByAction : TRUE;
		
		$breadcrumb[] 	= $function->label();
		$actionName 	= ($byAction) ? $action->label() : data_get($this->_data, 'actionName', '');
		
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