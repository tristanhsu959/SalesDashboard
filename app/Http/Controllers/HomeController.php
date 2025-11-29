<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class HomeController extends Controller
{
	private $_service;
	private $_currentAction = NULL;
	
	public function __construct()
	{
	}
	
	public function index(Request $request)
	{
		return view('home');
	}
	
}