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
	
	<form action="" method="post" x-ref="userListForm">
		@csrf
		<div class="user-list">
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
{{--
@if($viewModel->status() === TRUE)
	<form action="" method="post" id="userListForm">
	@csrf
	</form>

	@if ($viewModel->canQuery())
	<section class="searchbar section-wrapper">
		<form action="{{ route('user.search') }}" method="post" id="searchForm">
		@csrf
		<div class="input-field field-light-blue field">
			<input type="text" class="form-control valid" id="searchAd" name="searchAd" value="{{ $viewModel->getSearchAd() }}" maxlength="20" placeholder=" ">
			<label for="searchAd" class="form-label">AD帳號</label>
		</div>
		<div class="input-field field-light-blue field">
			<input type="text" class="form-control valid" id="searchName" name="searchName" value="{{ $viewModel->getSearchName() }}" maxlength="20" placeholder=" ">
			<label for="searchName" class="form-label">顯示名稱</label>
		</div>
		<div class="input-select field-light-blue field">
			<select class="form-select" id="searchArea" name="searchArea">
				<option value="">請選擇</option>
				@foreach($viewModel->option['area'] as $area)
				<option value="{{ $area->value }}" @selected($viewModel->selectedSearchArea($area->value)) >{{ $area->label() }}</option>
				@endforeach
			</select>
			<label for="group" class="form-label">管理區域</label>
		</div>
		<button class="btn btn-search" type="button">
			<span class="material-symbols-outlined filled-icon">search</span>
		</button>
		<button class="btn btn-search-reset" type="button">
			<span class="material-symbols-outlined filled-icon">backspace</span>
		</button>
		</form>
	</section>
	

	<section class="user-list section-wrapper">
		<!-- Create button -->
		@if($viewModel->canCreate())
		<div class="grid-header">
			<a href="{{ route('user.create') }}" class="btn btn-list-create">
				<span class="material-symbols-outlined filled-icon">add</span>
			</a>
		</div>
		@endif
		
		@if(empty(($viewModel->list)))
		<div class="container-fluid empty-list">
			<div class="row">
				<div class="col">查無符合資料</div>
			</div>
		</div>
		@else
		<div class="container-fluid list-data grid grid-cyan">
			<div class="row head">
				<div class="col col-1">#</div>
				<div class="col">AD帳號</div>
				<div class="col">顯示名稱</div>
				<div class="col col-4">管理區域</div>
				<div class="col">權限身份</div>
				<div class="col">更新時間</div>
				<div class="col col-action">操作</div>
			</div>
			@foreach($viewModel->list as $idx => $user)
			<div class="row">
				<div class="col col-1">{{ $idx + 1 }}</div>
				<div class="col">{{ $user['userAd'] }}</div>
				<div class="col">{{ $user['userDisplayName'] }}</div>
				<div class="col col-4 col-area">
					@foreach($user['roleArea'] as $area)
					<div class="badge">{{ Area::getLabelByValue($area) }}</div>
					@endforeach
				</div>
				<div class="col">{{ $viewModel->getRoleById($user['userRoleId']) }}</div>
				<div class="col">{{ $user['updateAt'] }}</div>
				<div class="col col-action">
					<a href="{{ route('user.update', [$user['userId']]) }}" class="btn btn-list-edit @disabled(! ($viewModel->canUpdate() && $viewModel->canUpdateThisUser($user['roleGroup'])))">
						<span class="material-symbols-outlined">edit</span>
					</a>
					<a href="{{ route('user.delete.post', [$user['userId']]) }}" class="btn btn-list-delete @disabled(! ($viewModel->canDelete() && $viewModel->canDeleteThisUser($user['roleGroup'])))">
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
@endsection()