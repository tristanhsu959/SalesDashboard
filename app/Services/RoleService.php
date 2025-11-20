<?php

namespace App\Services;

use App\Libraries\ResponseLib;
use App\Traits\MenuTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Exception;

class RoleService
{
	use MenuTrait;
	
	private $_repository;
    
	public function __construct()
	{
		// $this->_repository = $partyRepository;
	}
	
	public function getList()
	{
		return $result = ResponseLib::initialize([])->fail('test')->get();
	}
	
	public function createRole()
	{
		return ResponseLib::initialize()->success()->get();
	}
}
