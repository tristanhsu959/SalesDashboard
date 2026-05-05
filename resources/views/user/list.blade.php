@extends('layouts.app')
@use('App\Enums\Area')

@push('styles')
    <link href="{{ asset('styles/user/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/user/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Content -->
	<header class="page-nav">
		<nav>
			<a href="{{ route('user.create') }}" class="btn-create button circle"><i>add</i></a>
			@if (! empty($viewModel->list))
			<nav class="no-space filter">
				<div class="field label border prefix field-filter-dark small">
					<i>filter_alt</i>
					<input type="text" x-model="$store.userFilter.filter">
					<label>篩選</label>
				</div>
				<button class="right-round" @click="$store.userFilter.reset()"><i>backspace</i></button>
			</nav>
			@endif
		</nav>
	</header>
	
@if($viewModel->status() === TRUE)	
	<form x-data='userList(@json($viewModel->list), @json($viewModel->options))' action="" method="post" x-ref="userListForm">
		@csrf
		<section class="user-list container">
			<article x-show="list.length == 0" class="error-container border">
				<div class="row">
					<i>info</i><div class="max">查無符合資料</div>
				</div>
			</article>
			
			<table x-show="list.length > 0" class="stripes border odd-cyan">
				<thead>
					<tr>
						<th class="min">#</th>
						<th>帳號</th>
						<th>顯示名稱</th>
						<th>部門</th>
						<th>EMail</th>
						<th>狀態</th>
						<th>更新時間</th>
						<th class="right-align">操作</th>
					</tr>
				</thead>
				<tbody>
				<template x-for="(user, idx) in filterUsers" :key="idx">
					<tr>
						<td x-text="idx+1"></td>
						<td x-text="user.userAccount"></td>
						<td x-text="user.userDisplayName"></td>
						<td x-text="user.department"></td>
						<td x-text="user.email"></td>
						<td>
							<i class="green-text" x-show="user.isActive">check_circle</i>
							<i class="red-text" x-show="! user.isActive">x_circle</i>
						</td>
						<td class="min" x-text="user.updateAt"></td>
						<td class="right-align action">
							<a :href="'{{ route('user.update', ['id' => 'USER_ID']) }}'.replace('USER_ID', user.userId)" class="btn-edit button circle small" :disabled="user.roleGroup == options.supervisorGroupId">
								<i class="small">edit</i>
							</a>
							<a @click.prevent="confirmDelete($el.href)" :href="'{{ route('user.delete', ['id' => 'USER_ID']) }}'.replace('USER_ID', user.userId)" class="btn-delete button circle small" :disabled="user.roleGroup == options.supervisorGroupId">
								<i class="small">delete</i>
							</a>
						</td>
						
					</tr>
				</template>
				</tbody>
			</table>
			
		</section>
	</form>
@endif
<!-- Content -->
@endsection