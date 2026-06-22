<?php

namespace App\ViewModels;

use App\Facades\AppManager;
use App\Enums\FormAction;
use App\Enums\Functions;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use Illuminate\Support\Fluent;

class HomeViewModel extends Fluent
{
	use attrStatus, attrActionBar;
	
	public function __construct()
	{
		$this->function 	= Functions::HOME;
		$this->action		= FormAction::HOME;
		$this->backRoute	= FALSE;
		$this->success();	
		$this->statistics 	= [];
	}
	
	/* initialize
	 * @params: enum
	 * @params: string
	 * @params: string
	 * @return: void
	 */
	public function initialize()
	{
		$this->statistics = [];
		#$this->_setOptions();
	}
	
	/* Partial view
	 * @params: string
	 * @return: string
	 */
	public function getPartialView()
	{
		$type = $this->get('search.type', NULL);
		
		return match($type)
		{
			'store'		=> 'shipments.store',
			'factory'	=> 'shipments.factory',	 
		};
	}
	
	/* Output js */
	/*有額外資訊能獨立加入,故要寫在Base*/
	public function responseData()
	{
		#filter tool
		$type = data_get($this->statistics, 'modeType', NULL);
		$data = data_get($this->statistics, 'data', []);
		
		$response['hasFilter'] = ($type == 'store' && !empty($data));
		$response['hasResult'] = !empty($data);
		
		return $response;
	}
}