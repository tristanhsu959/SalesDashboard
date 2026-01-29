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
	
	public $currentUser;
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->currentUser = $this->getCurrentUser(); 
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
		return view('components.profile');
    }
	
}
