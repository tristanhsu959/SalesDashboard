{{--@inject('viewHelper', 'App\ViewHelpers\NewReleaseHelper')--}}
@use('App\Enums\RoleGroup')
@use('App\Enums\Area')

@extends('layouts.master')

@push('styles')
	<link href="{{ asset('styles/user/detail.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/user/create.js') }}"></script>
@endpush

@section('navHead', '帳號管理 | 新增')

@section('navAction')
<a href="{{ url('users/list') }}" class="btn btn-return">
	<span class="title">回列表</span>
	<span class="material-symbols-outlined filled-icon">arrow_forward</span>
</a>
@endsection

@section('content')
@if($status === TRUE)
		
<form action="{{ url('users') }}" method="post" id="userForm">
@csrf
<section class="section-wrapper">
	<div class="section user-data">
		<div class="input-field field-cyan field-dark field">
			<input type="text" class="form-control valid" id="account" name="account" maxlength="15" placeholder=" " required>
			<label for="account" class="form-label">AD帳號</label>
		</div>
		<div class="input-field field-cyan field-dark field">
			<input type="text" class="form-control valid" id="displayName" name="displayName" maxlength="15" placeholder=" ">
			<label for="displayName" class="form-label">顯示名稱</label>
		</div>
		<div class="input-select field-cyan field-dark field">
			<select class="form-select" id="area" name="area">
				<option value=""selected>請選擇</option>
				@foreach(Area::cases() as $area)
				<option value="{{ $area->value }}">{{ $area->label() }}</option>
				@endforeach
			</select>
			<label for="group" class="form-label">管理區域</label>
		</div>
		<div class="input-select field-cyan field-dark field">
			<select class="form-select" id="role" name="role">
				<option value=""selected>請選擇</option>
				@foreach(RoleGroup::cases() as $role)
				<option value="{{ $role->value }}">{{ $role->label() }}</option>
				@endforeach
			</select>
			<label for="group" class="form-label">權限身份</label>
		</div>
	</div>
	<div class="section user-role">
		<label class="form-check-label" for="flexRadioDefault1">
			<input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault1">
			帳號管理員
		</label>
		
		<label class="form-check-label" for="flexRadioDefault11">
			<input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault11">
			帳號管理員
		</label>
		<label class="form-check-label" for="flexRadioDefault111">
			<input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault111">
			帳號管理員
		</label>
		<label class="form-check-label" for="flexRadioDefault1111">
			<input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault1111">
			帳號管理員
		</label>
		<label class="form-check-label" for="flexRadioDefault11111">
			<input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault11111">
			帳號管理員
		</label>
		<label class="form-check-label" for="flexRadioDefault111111">
			<input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault111111">
			帳號管理員
		</label>
		<label class="form-check-label" for="flexRadioDefault1111111">
			<input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault1111111">
			帳號管理員
		</label>
	</div>
	<div class="toolbar">
		<button type="button" class="btn btn-primary btn-major">儲存</button>
		<button type="button" class="btn btn-red">取消</button>
	</div>
</section>
@endif
@endsection()