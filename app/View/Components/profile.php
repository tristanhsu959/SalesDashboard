<?php

namespace App\View\Components;

use App\Traits\AuthTrait;
use App\Models\CurrentUser;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;


class profile extends Component
{
	use AuthTrait;
	
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
		$currentUser = $this->getCurrentUser(); 
        return view('components.profile', ['currentUser' => $currentUser]);
    }
	
}
