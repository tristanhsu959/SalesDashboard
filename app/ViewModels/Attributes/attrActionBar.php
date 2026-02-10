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
	
	/* Get breadcrumb use default logic
	 * @params: 
	 * @return: array
	 */
	public function getBreadcrumbByDefault()
	{
		$breadcrumb 	= [];
		$function		= $this->_function;
		$action 		= data_get($this->_data, 'action', '');
		
		$breadcrumb[] 	= $function->label();
		$breadcrumb[] 	= $action->label();
		
		return $breadcrumb;
	}
	
	/* Get back route name
	 * @params: 
	 * @return: array
	 */
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