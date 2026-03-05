<?php

namespace App\ViewModels;

use App\Services\NewItemService;
use App\Enums\FormAction;
use App\Enums\Functions;
use App\Enums\Brand;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use App\ViewModels\Attributes\attrAllowAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;

class NewItemViewModel extends Fluent
{
	use attrStatus, attrActionBar, attrAllowAction;
	
	public function __construct(protected NewItemService $_service)
	{
		$this->function		= Functions::PRODUCT;
		$this->action 		= FormAction::LIST; 
		$this->backRoute 	= 'new_items';
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
		$this->set('options.brands', Brand::toArray()); 
		
		if ($this->action != FormAction::LIST)
			$this->set('options.products', $this->_service->getProductOptions()); 
	}
	
	/* Form submit action
	 * @params: 
	 * @return: string
	 */
	public function getFormAction() : string
    {
		return match($this->action)
		{
			FormAction::CREATE => route('new_item.create.post'),
			FormAction::UPDATE => route('new_item.update.post'),
		};
	}
	
	/* Keep form data
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: void
	 */
	public function keepFormData($id = 0, $brand = Brand::BAFANG->value, $productId = 0, $name = '', $saleDate = '', $tasteKeyWord = '', $status = TRUE, $updateAt = '')
    {
		$this->set('formData.id', $id);
		$this->set('formData.brand', $brand);
		$this->set('formData.productId', $productId);
		$this->set('formData.name', $name);
		$this->set('formData.saleDate', $saleDate);
		$this->set('formData.tasteKeyWord', is_array($tasteKeyWord) ? implode("\r\n", $tasteKeyWord) : $tasteKeyWord);
		$this->set('formData.status', $status);
		$this->set('formData.updateAt', $updateAt);
	}
}