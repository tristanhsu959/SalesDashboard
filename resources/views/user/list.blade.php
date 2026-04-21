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
	<!-- Search panel -->
	<form x-data='searchUser(@json($viewModel->search), @json($viewModel->options))' action="{{ route('user.search') }}" method="post" id="searchForm" class="no-margin" novalidate @submit.prevent="search()">
		@csrf
		<dialog id="searchPanel" class="right">
			<h5>查詢</h5>
			<div class="field label border round field-light-blue">
				<input x-model="searchData.ad" type="text" name="searchAd" maxlength="20">
				<label>AD帳號</label>
			</div>
			<div class="field label border round field-light-blue">
				<input x-model="searchData.name" type="text" name="searchName" maxlength="20">
				<label>顯示名稱</label>
			</div>
			<div class="field label suffix round border field-light-blue">
				<select x-model="searchData.roleId" name="searchRoleId">
					<option value="">請選擇</option>
					<template x-for="(name, roleId) in options.roleList" :key="roleId">
						<option :value="roleId" x-text="name" :selected="searchData.roleId == roleId"></option>
					</template>
				</select>
				<label>身份</label>
				<i>arrow_drop_down</i>
			</div>
			
			<nav class="right-align group split">
				<button type="submit" class="btn-search left-round large"><i>search</i>查詢</button>
				<button @click="reset" type="button" class="btn-search-reset right-round square large"><i>backspace</i></button>
			</nav>
		</dialog>
	</form>
	<!-- Search panel end -->
	
	<header class="page-nav">
		<nav>
			<button type="button" class="btn-show-search button circle" data-ui="#searchPanel"><i>search</i></button>
			<a href="{{ route('user.create') }}" class="btn-create button circle"><i>add</i></a>
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
						<th>AD帳號</th>
						<th>顯示名稱</th>
						<th>身份</th>
						<th>管理區域</th>
						<th>更新時間</th>
						<th class="right-align">操作</th>
					</tr>
				</thead>
				<tbody>
				<template x-for="(user, idx) in list" :key="idx">
					<tr>
						<td x-text="idx+1"></td>
						<td x-text="user.userAd"></td>
						<td x-text="user.userDisplayName"></td>
						<td x-text="user.roleName"></td>
						<td class="col-area relative">
							<span>查看</span>
							<div class="tooltip max white border shadow">
							
							<template x-if="user.roleArea.length == 0">
								<div class="chip round red white-text">未設定</div>
							</template>
							
							<template x-if="user.roleArea.length > 0" x-for="areaId in user.roleArea">
								<div class="chip round cyan white-text" x-text="options.areas"></div>
							</template>
							</div>
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