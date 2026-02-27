@extends('layouts.app')
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
@if($viewModel->status() === TRUE && $viewModel->hasPermission())

	<header class="page-nav">
		<nav>
			<a href="{{ route('role.create') }}" class="btn-create button circle"><i>add</i></a>
		</nav>
	</header>
	
	<form action="" method="post" x-ref="roleListForm">
		@csrf
		<div class="role-list">
			@if(empty(($viewModel->list)))
			<article class="error-container border">
				<div class="row">
					<i>info</i><div class="max">查無符合資料</div>
				</div>
			</article>
			@else
			<table class="stripes border" x-data="roleList">
				<thead>
					<tr>
						<th class="min">#</th>
						<th>身份</th>
						<th>權限群組</th>
						<th>管理區域</th>
						<th>更新時間</th>
						<th class="right-align">操作</th>
					</tr>
				</thead>
				<tbody>
				@foreach($viewModel->list as $idx => $role)
					<tr>
						<td>{{ $idx + 1 }}</td>
						<td>{{ $role['roleName'] }}</td>
						<td>{{ RoleGroup::getLabelByValue($role['roleGroup']) }}</td>
						<td>
							@foreach($role['roleArea'] as $area)
							<div class="chip round primary-container">{{ Area::getLabelByValue($area) }}</div>
							@endforeach
						</td>
						<td>{{ $role['updateAt'] }}</td>
						<td class="right-align">
							<a href="{{ route('role.update', [$role['roleId']]) }}" class="btn-edit button circle small" @disabled(! $viewModel->canUpdateThisRole($role['roleGroup']))>
								<i class="small">edit</i>
							</a>
							<a @click.prevent="confirmDelete($el.href)" href="{{ route('role.delete.post', [$role['roleId']]) }}" class="btn-delete button circle small" @disabled(! $viewModel->canDeleteThisRole($role['roleGroup']))>
								<i class="small">delete</i>
							</a>
						</td>
					</tr>
				@endforeach
				</tbody>
			</table>
			@endif
		</div>
	</form>
@endif

{{--
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
	@else
	<div class="alert alert-danger">
		無查詢權限
	</div>
	@endif
@endif
--}}
<!-- Content -->
@endsection()