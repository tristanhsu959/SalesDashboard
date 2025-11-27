<?php

namespace App\Enums;

enum FormAction : int
{
	case HOME 	= 1;
    case List 	= 2;
	case CREATE	= 3;
	case UPDATE = 4;
	case DELETE = 5;
	
	public function label() : string
    {
        return match ($this) 
		{
			self::HOME		=> '首頁',
			self::List 		=> '列表',
			self::CREATE	=> '新增',
			self::UPDATE 	=> '編輯',
			self::DELETE 	=> '刪除',
		};
    }
}
