<?php

namespace App\Services;

use App\Traits\MenuTrait;

class HomeService
{
	use MenuTrait;
	
	private $_repository;
    
	public function __construct()
	{
		// $this->_repository = $partyRepository;
	}
	
}
