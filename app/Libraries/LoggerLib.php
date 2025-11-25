<?php

namespace App\Libraries;

use Illuminate\Support\Str;
use Log;

class LoggerLib
{
	public function __construct()
	{
	}
	
	/* initialize
	 * @params: array
	 * @return: object
	 */
    public static function initialize()
    {
		return new LoggerLib();
    }
	
	/* initialize
	 * @params: array
	 * @return: object
	 */
    public function sysLog($title, $class, $function, $msg)
    {
		Log::channel('webSysLog')->error("{$title} [{$class}::{$function}] {$msg}");
    }
}