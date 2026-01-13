<?php

namespace App\Enums;

enum RoleGroup : int
{
	case SUPERVISOR	= 1;
    #case SUPERUSER	= 2; #for董總(無帳號管理)
	case ADMIN 		= 2;
	case USER 		= 3;
	
	public function label() : string
    {
        return match ($this) 
		{
			self::SUPERVISOR	=> 'SuperVisor',
			#self::SUPERUSER		=> 'SuperUser',
			self::ADMIN 		=> '帳號管理員',
			self::USER 			=> '使用者',
        };
    }
	
	public static function getLabelByValue($value) : string
	{
		#型別要一樣
		$value = intval($value);
		
		return match($value)
		{
			self::SUPERVISOR->value	=> self::SUPERVISOR->label(),
			#self::SUPERUSER->value	=> self::SUPERUSER->label(),
			self::ADMIN->value		=> self::ADMIN->label(),
			self::USER->value 		=> self::USER->label(),
			default => 'UNKNOW',
		};
	}
	
	#Supervisor排除
	public static function getEnabledList() : array
	{
		$enableList = [];
		$list = self::cases();
		
		foreach($list as $item)
		{
			if ($item->value != self::SUPERVISOR->value)
				$enableList[] = $item;
		}
		
		return $enableList;
	}
}
