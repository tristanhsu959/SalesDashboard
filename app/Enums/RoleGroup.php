<?php

namespace App\Enums;

enum RoleGroup : int
{
    case ADMIN	= 999;
	case USER 	= 1;
	
	public function label() : string
    {
        return match ($this) 
		{
			self::ADMIN	=> '帳號管理員',
			self::USER 	=> '使用者',
        };
    }
}
