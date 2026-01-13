@extends('layouts.master')
@use('App\Enums\RoleGroup')
@use('App\Enums\Area')

@push('styles')
	<link href="{{ asset('styles/role/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/role/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Content -->
@if($viewModel->status() === TRUE)
	<form action="" method="post" id="roleListForm">
	@csrf
	</form>

	@if ($viewModel->canQuery())
	<section class="role-list section-wrapper">
		<!-- Create button -->
		@if($viewModel->canCreate())
		<div class="grid-header">
			<a href="{{ route('role.create') }}" class="btn btn-list-create">
				<span class="material-symbols-outlined filled-icon">add</span>
			</a>
		</div>
		@endif
		
		<!-- List -->
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
				<div class="col col-4">管理區域</div>
				<div class="col">更新時間</div>
				<div class="col col-action">操作</div>
			</div>
			@foreach($viewModel->list as $idx => $role)
			<div class="row">
				<div class="col col-1">{{ $idx + 1 }}</div>
				<div class="col">{{ $role['roleName'] }}</div>
				<div class="col">{{ RoleGroup::getLabelByValue($role['roleGroup']) }}</div>
				<div class="col col-4 col-area">
					@foreach($role['roleArea'] as $area)
					<div class="badge">{{ Area::getLabelByValue($area) }}</div>
					@endforeach
				</div>
				<div class="col">{{ $role['updateAt'] }}</div>
				<div class="col col-action">
					<a href="{{ route('role.update', [$role['roleId']]) }}" class="btn btn-list-edit @disabled(! ($viewModel->canUpdate() && $viewModel->canUpdateThisRole($role['roleGroup'])))">
						<span class="material-symbols-outlined">edit</span>
					</a>
					<a href="{{ route('role.delete.post', [$role['roleId']]) }}" class="btn btn-list-delete @disabled(! ($viewModel->canDelete() && $viewModel->canDeleteThisRole($role['roleGroup'])))">
						<span class="material-symbols-outlined">delete</span>
					</a>
				</div>
			</div>
			@endforeach
		</div>
		@endif
	</section>
	@endif
@endif
<!-- Content -->
@endsection()