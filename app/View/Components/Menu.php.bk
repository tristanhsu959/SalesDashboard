<?php

namespace App\View\Components;

use App\Facades\AppManager;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Menu extends Component
{
	/**
     * Create a new component instance.
     */
    public function __construct()
    {
    }
	
	/**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
		return view('components.menu')->with('menu', AppManager::getAuthMenu());
    }
	
	public function isActive(string $url): string
	{
		$segments = request()->segments(); 
		$segmentsStr = Arr::join($segments, '/');
		
		return Str::contains($segmentsStr, $url) ? 'active' : '';
	}
}
