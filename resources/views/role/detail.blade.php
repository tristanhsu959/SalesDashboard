{{--@inject('viewHelper', 'App\ViewHelpers\NewReleaseHelper')--}}
@use('App\Enums\RoleGroup')
@use('App\Enums\Operation')

@extends('layouts.master')

@push('styles')
	<link href="{{ asset('styles/role/detail.css') }}" rel="stylesheet">
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
		
<form action="{{ route('auth') }}" method="post" id="roleForm">
@csrf
<section class="section-wrapper">
	<div class="section role-data">
		<div class="input-field field-orange field-dark">
			<input type="text" class="form-control valid" id="name" name="name" maxlength="10" placeholder=" " required>
			<label for="name" class="form-label">身份</label>
		</div>
		<div class="input-select field-orange field-dark">
			<select class="form-select" id="group" name="group">
				<option value=""selected>請選擇</option>
				@foreach(RoleGroup::cases() as $role)
				<option value="{{ $role->value }}">{{ $role->label() }}</option>
				@endforeach
			</select>
			<label for="group" class="form-label">權限群組</label>
		</div>
	</div>
	
	<div class="section role-permission">
		@foreach($data['permissionList'] as $groupKey => $group)
		<ul class="list-group">
			<label class="title">{{ $group['groupName'] }}</label>
			@foreach($group['items'] as $itemKey => $item)
			<li class="list-group-item">
				<div class="form-check form-switch permission-group">
					<input class="form-check-input" type="checkbox" id="item{{ $groupKey.$itemKey }}">
					<label class="form-check-label" for="item{{ $groupKey.$itemKey }}">{{ $item['name'] }}</label>
				</div>
				<div class="permission-group-items">
					@foreach($item['operation'] as $opKey => $operation)
					<label class="form-check-label" for="operation{{$groupKey.$itemKey.$opKey }}">
						<input class="form-check-input" type="checkbox" value="" id="operation{{$groupKey.$itemKey.$opKey }}">
						{{ $operation->label() }}
					</label>
					@endforeach
				</div>
			</li>
			@endforeach
		</ul>
		@endforeach
	</div>
	<div class="toolbar">
		<button type="button" class="btn btn-primary btn-major">儲存</button>
		<button type="button" class="btn btn-red">取消</button>
	</div>
</section>
@endif
@endsection()