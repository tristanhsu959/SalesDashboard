<?php

namespace App\ViewModels\Attributes;

use App\Enums\FormAction;
use Illuminate\Support\Fluent;

#Search form依功能狀況較不一致故不抽出
#Statistics response
trait attrResponse
{
	/* Statistics的回傳data */
	public function responseData()
	{
		$token 		= data_get($this->statistics, 'exportToken', NULL);
		$brandId 	= data_get($this->statistics, 'brandId', NULL);
		
		$data['status'] 		= $this->status();
		$data['isInit']			= empty($this->statistics); #判別是否有執行查詢
		$data['hasResult'] 		= ! empty($brandId); #不用token,因不是都有下載
		$data['exportAction'] 	= empty($token) ? '' : $this->getFormAction(FormAction::EXPORT);
		$data['brandCode']		= $this->brand->code(); #判別當前的功能brand, 故不能取自statistics
			
		return $data;
	}
	
	#為了方便管理,獨立取
	public function statisticsData($target = NULL)
	{
		if (is_null($target))
			return $this->statistics;
		
		return $this->get("statistics.{$target}");
	}
}