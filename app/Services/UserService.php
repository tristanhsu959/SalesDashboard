<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Libraries\ResponseLib;
use App\Enums\RoleGroup;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Exception;
use Log;

class UserService
{
	public function __construct(protected UserRepository $_repository)
	{
	}
	
	/* 取帳號清單(Get ALL)
	 * @params: 
	 * @return: array
	 */
	public function getList()
	{
		try
		{
			$list = $this->_repository->getList();
			
			return ResponseLib::initialize($list)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取帳號清單時發生錯誤');
		}
	}
	
	/* Create Account
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	public function createUser($account, $password, $displayName, $department, $email, $isActive, $permission, $area)
	{
		try
		{
			#1.Check account
			if ($this->_isAccountExist($account) === TRUE)
				throw new Exception('此帳號已存在');
			
			#2.Hash password
			$password = Hash::make($password);
			
			#3. Create user
			$this->_repository->insert($account, $password, $displayName, $department, $email, $isActive, RoleGroup::USER->value, $permission, $area);
		
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail($e->getMessage());
		}
	}
	
	/* 驗證帳號
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	private function _isAccountExist($account, $exceptId = FALSE)
	{
		try
		{
			$id = $this->_repository->getIdByAccount($account, $exceptId); 
			
			return empty($id) ? FALSE : TRUE;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			throw new Exception('驗證帳號發生錯誤');
		}
	}
	
	/* Update:取User Data
	 * @params: int
	 * @return: array
	 */
	public function getUserById($id)
	{
		try
		{
			$result = $this->_repository->getById($id); 
			return ResponseLib::initialize($result)->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('讀取帳號資料發生錯誤');
		}
	}
	
	/* Update User
	 * @params: int
	 * @params: string
	 * @params: string
	 * @params: int
	 * @return: array
	 */
	public function updateUser($userId, $adAccount, $displayName, $roleId)
	{
		try
		{
			$this->_repository->update($userId, $adAccount, $displayName, $roleId);
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('編輯帳號失敗');
		}
	}
	
	/* Remove User
	 * @params: int
	 * @return: array
	 */
	public function deleteUser($userId)
	{
		try
		{
			$this->_repository->remove($userId);
			return ResponseLib::initialize()->success();
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return ResponseLib::initialize()->fail('刪除帳號失敗');
		}
	}
	
	/* 取可設定的Role選項清單
	 * @params: 
	 * @return: array
	 */
	public function getRoleOptions()
	{
		try
		{
			$list = $this->_repository->getRoleList();
			
			$list = Arr::mapWithKeys($list, function (array $item, int $key) {
				return [$item['roleId'] => $item['roleName']];
			});
			
			return $list;
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			return [];
		}
	}
	
}
