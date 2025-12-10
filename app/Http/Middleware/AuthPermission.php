<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\AuthorizationTrait;

class AuthPermission
{
	use AuthorizationTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
		$signinUser = $this->getSigninUserInfo();
		
		if (empty($signinUser))
			return redirect()->route('signin')->with('msg', '認證已失效，請重新登入');
		
		if (empty($signinUser['Permission']))
			return redirect()->route('signin')->with('msg', '使用者尚無系統授權');
		
        return $next($request);
    }
}
