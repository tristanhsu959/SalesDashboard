<?php

namespace App\ViewModels;

use App\Enums\FormAction;
use App\Enums\Functions;
use App\Enums\Brand;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;

class PurchaseProductViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct()
	{
		$this->function		= Functions::PURCHASE_PRODUCT;
		$this->action 		= FormAction::LIST; 
		$this->backRoute 	= 'purchase_product';
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
		#$this->_setOptions();
	}
	
	/* Form所屬的參數選項
	 * @params:  
	 * @return: void
	 */
	/* private function _setOptions()
	{
		$this->set('options.brands', Brand::toArray()); 
		
		if ($this->action != FormAction::LIST)
			$this->set('options.products', $this->_service->getProductList()); 
	} */
	
	/* Form submit action
	 * @params: 
	 * @return: string
	 */
	public function getFormAction() : string
    {
		return match($this->action)
		{
			FormAction::UPDATE => route('purchase_product.setting.post'),
		};
	}
	
	/* Keep form data
	 * @params: int
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: void
	 */
	public function keepFormData($productCodes = [], $updateAt = '')
    {
		#不分brand
		$this->set('formData.productCodes', $productCodes);
		$this->set('formData.updateAt', $updateAt);
	}
}