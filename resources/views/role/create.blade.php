{{--@inject('viewHelper', 'App\ViewHelpers\NewReleaseHelper')--}}
@extends('layouts.master')

@push('styles')
	<link href="{{ asset('styles/role/role.css') }}" rel="stylesheet">
    <link href="{{ asset('styles/role/create.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/role/create.js') }}"></script>
@endpush

@section('navHead', '身份管理 | 新增')

@section('navAction')
<a href="{{ url('roles/list') }}" class="btn btn-return">
	<span class="title">回列表</span>
	<span class="material-symbols-outlined filled-icon">arrow_forward</span>
</a>
@endsection

@section('content')
@if($status === TRUE)
		
<form action="{{ route('auth') }}" method="post" id="loginForm">
@csrf
<section class="section-wrapper">
	<div class="section role-data">
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
		<div class="input-select">
			<select class="form-select" id="group" name="group">
				<option value="-1"selected>請選擇</option>
				<option value="1">Administrator</option>
				<option value="2">User</option>
			</select>
			<label for="jobtitle" class="form-label">權限群組</label>
		</div>
		
	</div>
	
	<div class="section role-permission">
		<ul class="list-group">
			<label class="title">權限管理</label>
			<li class="list-group-item">
				<div class="form-check form-switch permission-group">
					<input class="form-check-input" type="checkbox" id="flexSwitchCheckDefault">
					<label class="form-check-label" for="flexSwitchCheckDefault">角色管理</label>
				</div>
				<div class="permission-group-items">
					<label class="form-check-label" for="flexCheckDefault1">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault1">
						新增
					</label>
					<label class="form-check-label" for="flexCheckDefault1">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault1">
						新增
					</label>
					<label class="form-check-label" for="flexCheckDefault1">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault1">
						新增
					</label>
					<label class="form-check-label" for="flexCheckDefault1">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault1">
						新增
					</label>
					
				</div>
			</li>
			<li class="list-group-item">
				<div class="form-check form-switch permission-group">
					<input class="form-check-input" type="checkbox" id="flexSwitchCheckDefault">
					<label class="form-check-label" for="flexSwitchCheckDefault">角色管理</label>
				</div>
				<div class="permission-group-items">
					<div class="form-check permission-item">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
						<label class="form-check-label" for="flexCheckDefault">角色管理</label>
					</div>
					<div class="form-check permission-item">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
						<label class="form-check-label" for="flexCheckDefault">角色管理</label>
					</div>
				</div>
			</li>
		</ul>
		
		<ul class="list-group">
			<label class="title">權限管理</label>
			<li class="list-group-item">
				<div class="form-check form-switch permission-group">
					<input class="form-check-input" type="checkbox" id="flexSwitchCheckDefault">
					<label class="form-check-label" for="flexSwitchCheckDefault">角色管理</label>
				</div>
				<div class="permission-group-items">
					<div class="form-check permission-item">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
						<label class="form-check-label" for="flexCheckDefault">新增</label>
					</div>
					<div class="form-check permission-item">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
						<label class="form-check-label" for="flexCheckDefault">編輯</label>
					</div>
				</div>
			</li>
			<li class="list-group-item">
				<div class="form-check form-switch permission-group">
					<input class="form-check-input" type="checkbox" id="flexSwitchCheckDefault">
					<label class="form-check-label" for="flexSwitchCheckDefault">角色管理</label>
				</div>
				<div class="permission-group-items">
					<div class="form-check permission-item">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
						<label class="form-check-label" for="flexCheckDefault">角色管理</label>
					</div>
					<div class="form-check permission-item">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
						<label class="form-check-label" for="flexCheckDefault">角色管理</label>
					</div>
				</div>
			</li>
		</ul>
	</div>
</section>

<ul class="list-group">
			<label class="title">權限管理</label>
			<li class="list-group-item">
				<div class="form-check form-switch permission-group">
					<input class="form-check-input" type="checkbox" id="flexSwitchCheckDefault">
					<label class="form-check-label" for="flexSwitchCheckDefault">角色管理</label>
				</div>
				<div class="permission-group-items">
					<div class="form-check permission-item">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
						<label class="form-check-label" for="flexCheckDefault">新增</label>
					</div>
					<div class="form-check permission-item">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
						<label class="form-check-label" for="flexCheckDefault">編輯</label>
					</div>
				</div>
			</li>
			<li class="list-group-item">
				<div class="form-check form-switch permission-group">
					<input class="form-check-input" type="checkbox" id="flexSwitchCheckDefault">
					<label class="form-check-label" for="flexSwitchCheckDefault">角色管理</label>
				</div>
				<div class="permission-group-items">
					<div class="form-check permission-item">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
						<label class="form-check-label" for="flexCheckDefault">角色管理</label>
					</div>
					<div class="form-check permission-item">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
						<label class="form-check-label" for="flexCheckDefault">角色管理</label>
					</div>
				</div>
			</li>
		</ul>
		
</form>
<ul class="list-group">
			<label class="title">權限管理</label>
			<li class="list-group-item">
				<div class="form-check form-switch permission-group">
					<input class="form-check-input" type="checkbox" id="flexSwitchCheckDefault">
					<label class="form-check-label" for="flexSwitchCheckDefault">角色管理</label>
				</div>
				<div class="permission-group-items">
					<div class="form-check permission-item">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
						<label class="form-check-label" for="flexCheckDefault">新增</label>
					</div>
					<div class="form-check permission-item">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
						<label class="form-check-label" for="flexCheckDefault">編輯</label>
					</div>
				</div>
			</li>
			<li class="list-group-item">
				<div class="form-check form-switch permission-group">
					<input class="form-check-input" type="checkbox" id="flexSwitchCheckDefault">
					<label class="form-check-label" for="flexSwitchCheckDefault">角色管理</label>
				</div>
				<div class="permission-group-items">
					<div class="form-check permission-item">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
						<label class="form-check-label" for="flexCheckDefault">角色管理</label>
					</div>
					<div class="form-check permission-item">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
						<label class="form-check-label" for="flexCheckDefault">角色管理</label>
					</div>
				</div>
			</li>
		</ul>
@endif

@endsection()