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
@if($viewModel->status() === TRUE)
	
	<!-- Search panel -->
	<form x-data='searchUser(@json($viewModel->search))' action="{{ route('user.search') }}" method="post" id="searchForm" class="no-margin" novalidate @submit.prevent="search()">
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
					@foreach($viewModel->options['roleList'] as $id => $name)
						<option value="{{ $id }}" @selected($id == $viewModel->search['roleId'])>{{ $name }}</option>
					@endforeach
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
	
	<header class="page-nav" :class="isTop ? 'blue-grey10' : 'orange'">
		<nav>
			<button type="button" class="btn-show-search button circle" data-ui="#searchPanel"><i>search</i></button>
			<a href="{{ route('user.create') }}" class="btn-create button circle"><i>add</i></a>
		</nav>
	</header>
	
	<form x-data="userList" action="" method="post" x-ref="userListForm">
		@csrf
		<div class="user-list">
			@if(empty(($viewModel->list)))
			<article class="error-container border">
				<div class="row">
					<i>info</i><div class="max">查無符合資料</div>
				</div>
			</article>
			@else
			<table class="stripes border">
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
				@foreach($viewModel->list as $idx => $user)
					<tr>
						<td>{{ $idx + 1 }}</td>
						<td>{{ $user['userAd'] }}</td>
						<td>{{ $user['userDisplayName'] }}</td>
						<td>{{ $user['roleName'] }}</td>
						<td class="col-area relative">
							<span>查看</span>
							<div class="tooltip max white border shadow">
							@if (empty($user['roleArea']))
								<div class="chip round red white-text">未設定</div>
							@endif
							
							@foreach($user['roleArea'] as $area)
								<div class="chip round cyan white-text">{{ Area::tryFrom($area)->label() }}</div>
							@endforeach
							</div>
						</td>
						<td class="min">{{ $user['updateAt'] }}</td>
						<td class="right-align action">
							<a href="{{ route('user.update', [$user['userId']]) }}" class="btn-edit button circle small" @disabled(! $viewModel->canUpdateThisUser($user['roleGroup'])) >
								<i class="small">edit</i>
							</a>
							<a @click.prevent="confirmDelete($el.href)" href="{{ route('user.delete', [$user['userId']]) }}" class="btn-delete button circle small" @disabled(! $viewModel->canDeleteThisUser($user['roleGroup'])) >
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
@endsection