<?php

namespace App\Services;

use App\Libraries\ResponseLib;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Exception;

class UserService
{
	private $_repository;
    
	public function __construct()
	{
		// $this->_repository = $partyRepository;
	}
	
	public function getList()
	{
		return $result = ResponseLib::initialize([])->fail('test')->get();
	}
	
	public function createUser()
	{
		// $data['permissionList'] = config('web.menu');
		$data = [];
		return ResponseLib::initialize($data)->success()->get();
	}
}
