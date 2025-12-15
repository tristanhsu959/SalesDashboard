@extends('layouts.master')
@use('App\Enums\RoleGroup')

@push('styles')
	<link href="{{ asset('styles/role/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/role/list.js') }}" defer></script>
@endpush

@section('navHead', $viewModel->getBreadcrumb())

@section('navAction')
@if($viewModel->canCreate())
<a href="{{ route('role.create') }}" class="btn btn-create">
	<span class="material-symbols-outlined filled-icon">add</span>
	<span class="title">新增</span>
</a>
@endif
@endsection

@section('content')

@if($viewModel->status === TRUE)
	<form action="" method="post" id="roleListForm">
	@csrf
	</form>

	@if ($viewModel->canQuery())
	<section class="role-list section-wrapper">
		@if(empty(($viewModel->list)))
		<div class="container-fluid empty-list">
			<div class="row">
				<div class="col">查無符合資料</div>
			</div>
		</div>
		@else
		<div class="container-fluid list-data grid grid-purple">
			<div class="row head">
				<div class="col col-1">#</div>
				<div class="col">身份</div>
				<div class="col">權限群組</div>
				<div class="col col-action">操作</div>
			</div>
			@foreach($viewModel->list as $idx => $role)
			<div class="row">
				<div class="col col-1">{{ $idx + 1 }}</div>
				<div class="col">{{ $role['RoleName'] }}</div>
				<div class="col">{{ RoleGroup::getLabelByValue($role['RoleGroup']) }}</div>
				<div class="col col-action">
					@if($viewModel->canUpdate())
					<a href="{{ route('role.update', [$role['RoleId']]) }}" class="btn btn-edit">
						<span class="material-symbols-outlined">edit</span>
					</a>
					@endif
					@if($viewModel->canDelete())
					<a href="{{ route('role.delete.post', [$role['RoleId']]) }}" class="btn btn-delete">
						<span class="material-symbols-outlined">delete</span>
					</a>
					@endif
				</div>
			</div>
			@endforeach
		</div>
		@endif
	</section>
	@endif
@endif
@endsection()