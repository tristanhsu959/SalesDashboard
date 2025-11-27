@extends('layouts.master')
@use('App\Enums\RoleGroup')

@push('styles')
	<link href="{{ asset('styles/role/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/role/list.js') }}"></script>
@endpush

@section('navHead', $viewModel->getBreadcrumb())

@section('navAction')
<a href="{{ route('role.create') }}" class="btn btn-create">
	<span class="material-symbols-outlined filled-icon">add</span>
	<span class="title">新增</span>
</a>
@endsection

@section('content')

@if($viewModel->status === TRUE)
<section class="role-list section-wrapper">
	@if(empty(($viewModel->data)))
	<div class="container-fluid empty-list">
		<div class="row">
			<div class="col">查無符合資料</div>
		</div>
	</div>
	@else
	<div class="container-fluid list-data">
		<div class="row head">
			<div class="col col-1">#</div>
			<div class="col">身份</div>
			<div class="col">權限群組</div>
			<div class="col col-action">操作</div>
		</div>
		@foreach($viewModel->data as $idx => $role)
		<div class="row">
			<div class="col col-1">{{ $idx + 1 }}</div>
			<div class="col">{{ $role->RoleName }}</div>
			<div class="col">{{ RoleGroup::getLabelByValue($role->RoleGroup) }}</div>
			<div class="col col-action">
				<a href="{{ route('role.update', [$role->RoleId]) }}" class="btn btn-edit">
					<span class="material-symbols-outlined">edit</span>
				</a>
				<a href="{{ route('role.remove.post', [$role->RoleId]) }}" class="btn btn-del">
					<span class="material-symbols-outlined">delete</span>
				</a>
			</div>
		</div>
		@endforeach
	</div>
	@endif
</section>
@endif
@endsection()