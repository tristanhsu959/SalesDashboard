@use('App\Enums\RoleGroup')
@use('App\Enums\Operation')

@extends('layouts.master')

@push('styles')
	<link href="{{ asset('styles/role/detail.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/role/detail.js') }}" defer></script>
@endpush

@section('navHead', $viewModel->getBreadcrumb())

@section('navAction')
<a href="{{ route('role.list') }}" class="btn btn-return">
	<span class="title">回列表</span>
	<span class="material-symbols-outlined filled-icon">arrow_forward</span>
</a>
@endsection

@section('content')
<form action="{{ $viewModel->getFormAction() }}" method="post" id="roleForm" data-admin="{{ RoleGroup::ADMIN->value }}">
<input type="hidden" value="{{ $viewModel->getUpdateRoleId() }}" name="id">
@csrf

<section class="section-wrapper">
	<div class="section role-data">
		<div class="input-field field-orange field dark required">
			<input type="text" class="form-control" id="name" name="name" value="{{  $viewModel->getRoleName() }}" maxlength="10" placeholder=" " required>
			<label for="name" class="form-label">身份</label>
		</div>
		<div class="input-select field-orange field dark required">
			<label for="group" class="form-label">權限群組</label>
			<select class="form-select" id="group" name="group">
				<option value="">請選擇</option>
				@foreach($viewModel->roleGroup as $role)
				<option value="{{ $role->value }}" {{ $viewModel->selectedRoleGroup($role->value) }}>
				{{ $role->label() }}
				</option>
				@endforeach
			</select>
		</div>
	</div>
	
	<div class="section role-permission dark">
		@foreach($viewModel->functionList as $groupKey => $group)
		<ul class="list-group {{ Str::lower($group['groupType']) }}">
			<label class="title">
				<span class="material-symbols-outlined filled-icon">{{ $group['groupIcon']['name'] }}</span>
				{{ $group['groupName'] }}
			</label>
			@foreach($group['items'] as $itemKey => $item)
			<li class="list-group-item">
				<div class="form-check form-switch permission-group">
					<input class="form-check-input" type="checkbox" id="item{{ $groupKey.$itemKey }}">
					<label class="form-check-label" for="item{{ $groupKey.$itemKey }}">{{ $item['name'] }}</label>
				</div>
				<div class="permission-group-items">
					@foreach($item['operation'] as $opKey => $operation)
					<label class="form-check-label" for="settingList{{$groupKey.$itemKey.$opKey }}">
						<input class="form-check-input" type="checkbox" 
							value="{{ $operation->value }}" 
							id="settingList{{$groupKey.$itemKey.$opKey }}" 
							name="{{ Str::replaceArray('?', [$group['groupCode'], $item['actionCode']], 'settingList[?][?][]') }}"
							{{ $viewModel->checkedOperation($group['groupCode'], $item['actionCode'], $operation->value) }}
						>
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
		<button type="button" class="btn btn-primary btn-major btn-save">儲存</button>
		<button type="button" class="btn btn-red btn-cancel">取消</button>
	</div>
</section>
</form>

@endsection()