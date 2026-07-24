<?php

namespace App\ViewModels;

use App\Enums\FormAction;
use App\ViewModels\Attributes\attrStatus;
use Illuminate\Support\Fluent;

class forgetPasswordViewModel extends Fluent
{
	use attrStatus;
	
	public function __construct()
	{
		#給設定password form使用, 非login頁面的send link
		$this->action = FormAction::UPDATE; #不影響
		$this->keepFormData();
		$this->success();
	}
	
	/* Keep signin form data : account only, 以防會使用到
	 * @params: string
	 * @return: void
	 */
	public function keepFormData($account = '', $password = '')
    {
		$this->set('formData.account', $account);
		$this->set('formData.password', $password);
	}
	
	/* Output json
	 * @params: string
	 * @return: void
	 */
	public function responseData()
    {
		$formData['formAction']	= route('forgetPassword.send');
		$formData['account']	= '';
		
		return $formData;
	}
}