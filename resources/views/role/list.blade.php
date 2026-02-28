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

	<header class="page-nav" :class="isTop ? 'blue-grey10' : 'orange'">
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
						<td>{{ RoleGroup::tryFrom($role['roleGroup'])->label() }}</td>
						<td class="col-area">
							@foreach($role['roleArea'] as $area)
							<div class="chip round primary-container">{{ Area::tryFrom($area)->label() }}</div>
							@endforeach
						</td>
						<td>{{ $role['updateAt'] }}</td>
						<td class="right-align action">
							<a href="{{ route('role.update', [$role['roleId']]) }}" class="btn-edit button circle small" @disabled(! $viewModel->canUpdateThisRole($role['roleGroup']))>
								<i class="small">edit</i>
							</a>
							<a @click.prevent="confirmDelete($el.href)" href="{{ route('role.delete', [$role['roleId']]) }}" class="btn-delete button circle small" @disabled(! $viewModel->canDeleteThisRole($role['roleGroup']))>
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
<!-- Content -->
@endsection()