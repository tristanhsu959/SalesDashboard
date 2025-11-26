<?php

namespace App\Libraries;

use Illuminate\Support\Str;
use Log;

class LoggerLib
{
	private $_title;
	
	public function __construct($title)
	{
		$this->_title = $title;
	}
	
	/* initialize
	 * @params: array
	 * @return: object
	 */
    public static function initialize($title)
    {
		return new LoggerLib($title);
    }
	
	/* initialize
	 * @params: array
	 * @return: object
	 */
    public function sysLog($msg, $class = 'NA', $function = 'NA')
    {
		Log::channel('webSysLog')->error("{$this->_title} [{$class}::{$function}] {$msg}");
    }
}