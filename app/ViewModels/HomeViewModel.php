<?php

namespace App\ViewModels;

use App\Enums\FormAction;
use App\Enums\Functions;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use Illuminate\Support\Fluent;

class HomeViewModel extends Fluent
{
	use attrStatus, attrActionBar;
	
	public function __construct()
	{
		$this->function = Functions::HOME;
		$this->action	= NULL; #enum form action
		$this->backRoute= FALSE;
		$this->success();	#default
	}
}