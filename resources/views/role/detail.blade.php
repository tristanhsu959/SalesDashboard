@use('App\Enums\RoleGroup')
@use('App\Enums\Operation')
@use('App\Enums\Area')

@extends('layouts.master')

@push('styles')
	<link href="{{ asset('styles/role/detail.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/role/detail.js') }}" defer></script>
@endpush

@section('navHead', $viewModel->getBreadcrumb())

@section('navBack')
<a href="{{ route('role.list') }}" class="btn btn-return">
	<span class="material-symbols-outlined filled-icon">arrow_back</span>
	<span class="title">回列表</span>
</a>
@endsection

@section('content')
<form action="{{ $viewModel->getFormAction() }}" method="post" id="roleForm" data-admin="{{ RoleGroup::ADMIN->value }}">
<input type="hidden" value="{{ $viewModel->getRoleId() }}" name="id">
@csrf

<section class="section-wrapper dp-2">
	<div class="section role-data">
		<div class="input-field field-purple field required">
			<input type="text" class="form-control" id="name" name="name" value="{{  $viewModel->getRoleName() }}" maxlength="10" placeholder=" " required>
			<label for="name" class="form-label">身份</label>
		</div>
		<div class="input-select field-purple field required">
			<select class="form-select" id="group" name="group">
				<option value="">請選擇</option>
				@foreach($viewModel->optionRoleGroup as $role)
				<option value="{{ $role->value }}" @selected($viewModel->selectedRoleGroup($role->value)) >
				{{ $role->label() }}
				</option>
				@endforeach
			</select>
			<label for="group" class="form-label">權限群組</label>
		</div>
	</div>
	
	<div class="section role-permission field-group">
		@foreach($viewModel->optionFunctionList as $groupKey => $group)
		<ul class="list-group {{ Str::lower($group['type']) }}">
			<div class="divider"></div>
			<label class="title">
				<span class="material-symbols-outlined filled-icon">{{ $group['style']['icon'] }}</span>
				{{ $group['name'] }}
			</label>
			@foreach($group['items'] as $itemKey => $item)
			<li class="list-group-item">
				<div class="form-check form-switch permission-group">
					<input class="form-check-input" type="checkbox" id="item-{{ $groupKey.$itemKey }}">
					<label class="form-check-label" for="item-{{ $groupKey.$itemKey }}">{{ $item['name'] }}</label>
				</div>
				<div class="permission-group-items">
					@foreach($item['operation'] as $opKey => $operation)
					<label class="form-check-label" for="settingList-{{ $groupKey.$itemKey.$opKey }}">
						<input class="form-check-input" type="checkbox" 
							value="{{ $operation->value }}" 
							id="settingList-{{ $groupKey.$itemKey.$opKey }}" 
							name="{{ Str::replaceArray('?', [$item['code']], 'permissionSetting[?][]') }}"
							@checked($viewModel->checkedOperation($item['code'], $operation->value))
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
		<button type="button" class="btn btn-red btn-reset">重設</button>
	</div>
</section>
</form>

@endsection()