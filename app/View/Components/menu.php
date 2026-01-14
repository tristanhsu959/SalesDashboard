<?php

namespace App\View\Components;

use App\Traits\AuthTrait;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class menu extends Component
{
	use AuthTrait;
	
	private $_menu;
	
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->_menu = $this->getAuthMenu();
    }
	
	/**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.menu', ['menu' => $this->_menu]);
    }
	
	public function isActive(string $url): string
	{
		$segments = request()->segments(); 
		$segmentsStr = Arr::join($segments, '/');
		
		return Str::contains($segmentsStr, $url) ? 'active' : '';
	}
}
