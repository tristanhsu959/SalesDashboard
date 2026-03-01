<?php

namespace App\ViewModels;

use App\Enums\FormAction;
use App\Enums\Functions;
use App\Enums\Brand;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use Illuminate\Support\Fluent;

class ProductViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct()
	{
		$this->function		= Functions::PRODUCT;
		$this->action 		= FormAction::LIST; 
		$this->backRoute 	= 'products';
		$this->success();
	}
	
	/* initialize
	 * @params: enum
	 * @return: void
	 */
	public function initialize($action)
	{
		#初始化各參數及Form Options
		$this->action	= $action;
		$this->success();
		
		$this->_setOptions();
	}
	
	/* Form所屬的參數選項
	 * @params:  
	 * @return: void
	 */
	private function _setOptions()
	{
		$this->set('options.brands', Brand::cases());
	}
	
	/* Form submit action
	 * @params: 
	 * @return: string
	 */
	public function getFormAction() : string
    {
		return match($this->action)
		{
			FormAction::CREATE => route('product.create.post'),
			FormAction::UPDATE => route('product.update.post'),
		};
	}
	
	/* Keep form data
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: void
	 */
	public function keepFormData($id = 0, $brand = 0, $name = '', $primaryNo = '', $secondaryNo = '', $tasteNo = '', $status = TRUE)
    {
		$this->set('formData.id', $id);
		$this->set('formData.brand', $brand);
		$this->set('formData.name', $name);
		$this->set('formData.primaryNo', $primaryNo);
		$this->set('formData.secondaryNo', $secondaryNo);
		$this->set('formData.tasteNo', $tasteNo);
		$this->set('formData.status', $status);
		$this->set('formData.buygoodId', Brand::BUYGOOD->value); #給js用
	}
}