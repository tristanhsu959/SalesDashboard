<?php

namespace App\Services;

use App\Repositories\RoleRepository;
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
    
	public function __construct(RoleRepository $roleRepository)
	{
		$this->_repository = $roleRepository;
	}
	
	/* 取Role清單(Get ALL)
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		try
		{
			$list = $this->_repository->getList()->toArray();
			
			return ResponseLib::initialize($list)->success()->get();
		}
		catch(Exception $e)
		{
			return ResponseLib::initialize()->fail($e->getMessage())->get();
		}
	}
	
	/* 取Role清單(Get ALL)
	 * @params: 
	 * @return: array
	 */
	public function createRole()
	{
		$data['permissionList'] = config('web.menu');
		return ResponseLib::initialize($data)->success()->get();
	}
}
