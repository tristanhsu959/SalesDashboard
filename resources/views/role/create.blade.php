{{--@inject('viewHelper', 'App\ViewHelpers\NewReleaseHelper')--}}
@extends('layouts.master')

@push('styles')
	<link href="{{ asset('styles/role/role.css') }}" rel="stylesheet">
    <link href="{{ asset('styles/role/create.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/role/create.js') }}"></script>
@endpush

@section('content')

<nav class="page navbar">
	<span class="navbar-action">
		<span class="material-symbols-outlined filled-icon">bubble_chart</span>
		身份管理 | 新增
	</span>
	<span class="navbar-operation">
		<a href="{{ url('roles/list') }}" class="btn btn-return w3">
			<span class="title">回列表</span>
			<span class="material-symbols-outlined filled-icon">arrow_forward</span>
		</a>
	</span>
</nav>

{{--@if($status === TRUE)--}}
		
<section class="role-list section-wrapper container-fluid">
<form action="{{ route('auth') }}" method="post" id="loginForm">
	@csrf
	
	<div class="data-info">
		<div class="input-field field-orange">
			<input type="text" class="form-control valid" id="name" name="name" maxlength="10" placeholder=" " required>
			<label for="name" class="form-label">名稱</label>
		</div>
		<div class="input-field field-orange">
			<input type="text" class="form-control valid" id="department" name="department" maxlength="20" placeholder=" " required>
			<label for="department" class="form-label">部門</label>
		</div>
		<div class="input-field field-orange">
			<input type="text" class="form-control valid" id="jobtitle" name="jobtitle" maxlength="10" placeholder=" " required>
			<label for="jobtitle" class="form-label">職稱</label>
		</div>
		<select class="form-select col-3" id="group" name="group">
			<option selected>權限群組</option>
			<option value="1">Administrator</option>
			<option value="2">User</option>
		</select>
	</div>
	
	<div class="data-info">
		<div class="form-floating col-3">
			<input type="text" class="form-control" id="name" name="name" maxlength="10" placeholder="名稱">
			<label for="name">名稱</label>
		</div>
		<div class="form-floating col-3">
			<input type="text" class="form-control" id="department" name="department" maxlength="20" placeholder="部門">
			<label for="department">部門</label>
		</div>
		<div class="form-floating col-3">
			<input type="text" class="form-control" id="jobtitle" name="jobtitle" maxlength="10" placeholder="職稱">
			<label for="jobtitle">職稱</label>
		</div>
		<select class="form-select col-3" id="group" name="group">
			<option selected>權限群組</option>
			<option value="1">Administrator</option>
			<option value="2">User</option>
		</select>
	</div>
	<ul class="list-group data-permission">
		<li class="list-group-item">An item</li>
		<li class="list-group-item">A second item</li>
		<li class="list-group-item">A third item</li>
		<li class="list-group-item">A fourth item</li>
		<li class="list-group-item">And a fifth one</li>
	</ul>
	
</form>
</section>


{{--@endif--}}
@endsection()