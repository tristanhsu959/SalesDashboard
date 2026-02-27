<?php

namespace App\ViewModels\Attributes;

use App\Enums\FormAction;
use Illuminate\Support\Facades\Request;

#Breadcrumb | backurl
trait attrActionBar
{
	/* Get breadcrumb use default logic
	 * @params: 
	 * @return: array
	 */
	public function breadcrumb()
	{
		$except = [FormAction::SIGNIN->value];
		$breadcrumb	= [];
		
		$brand 		= $this->get('brand', NULL);
		$function	= $this->function;
		$action 	= $this->get('action', NULL);
		
		if ($brand)
			$breadcrumb[] = $brand->label();
		
		$breadcrumb[] 	= $function->label();
		
		if ($brand)
			$breadcrumb[] = $action->label();
				
		return $breadcrumb;
	}
	
	/* Get back route name
	 * @params: 
	 * @return: array
	 */
	public function backRoute()
	{
		$except = [FormAction::SIGNIN->value, FormAction::HOME->value, FormAction::LIST->value];
		
		if (in_array($this->action->value, $except))
			return '';
		else
			return $this->backRoute;
	}
	
	/* Get all data for action bar
	 * @params: 
	 * @return: array
	 */
	public function actionBarData()
	{
		$data['isHome'] 	= request()->routeIs('home');
		$data['breadcrumb'] = $this->breadcrumb();
		$data['backRoute'] 	= $this->get('backRoute', FALSE);
		$data['homeRoute'] 	= route('home');
		
		return $data;
	}
}