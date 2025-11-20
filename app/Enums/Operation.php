<?php

namespace App\Enums;

enum Operation : string
{
    case CREATE	= '0001';
	case READ 	= '0002';
	case UPDATE = '0004';
	case DELETE = '0008';
	
	public function label() : string
    {
        return match ($this) 
		{
			self::CREATE	=> '新增',
			self::READ 		=> '查詢',
			self::UPDATE 	=> '編輯',
			self::DELETE 	=> '刪除',
        };
    }
}
