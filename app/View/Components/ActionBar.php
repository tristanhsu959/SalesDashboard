<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Arr;

class ActionBar extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(protected array $breadcrumb, protected $routeName)
    {
       
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.action-bar');
    }
	
	public function renderBreadcrumb()
	{
		$divider = '<span class="material-symbols-outlined">keyboard_arrow_right</span>';
		return Arr::join($this->breadcrumb, $divider);
	}
	
	public function getRoute()
	{
		return empty($this->routeName) ? '' : route($this->routeName);
	}
	
	public function active()
	{
		return empty($this->routeName) ? '' : 'active';
	}
}
