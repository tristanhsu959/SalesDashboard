@use('App\Enums\Area')
@extends('layouts.master')

@push('styles')
    <link href="{{ asset('styles/user/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/user/list.js') }}" defer></script>
@endpush

@section('navHead', $viewModel->getBreadcrumb())

@section('navAction')
@if($viewModel->canCreate())
<a href="{{ route('user.create') }}" class="btn btn-create">
	<span class="material-symbols-outlined filled-icon">add</span>
	<span class="title">新增</span>
</a>
@endif
@endsection

@section('content')
@if($viewModel->status === TRUE)
	<form action="" method="post" id="userListForm">
	@csrf
	</form>

	@if ($viewModel->canQuery())
	<form action="{{ route('user.search') }}" method="post" id="searchForm">
	@csrf
	<section class="searchbar section-wrapper">
		<div class="input-field field-lime dark field">
			<input type="text" class="form-control valid" id="searchAd" name="searchAd" maxlength="20" placeholder=" ">
			<label for="searchAd" class="form-label">AD帳號</label>
		</div>
		<div class="input-field field-lime dark field">
			<input type="text" class="form-control valid" id="searchName" name="searchName" maxlength="20" placeholder=" ">
			<label for="searchName" class="form-label">顯示名稱</label>
		</div>
		<div class="input-select field-lime dark field">
			<select class="form-select" id="searchArea" name="searchArea">
				<option value="">請選擇</option>
				@foreach($viewModel->area as $area)
				<option value="{{ $area->value }}">{{ $area->label() }}</option>
				@endforeach
			</select>
			<label for="group" class="form-label">管理區域</label>
		</div>
		<button class="btn btn-search btn-info" type="button">
			<span class="material-symbols-outlined filled-icon">search</span>
		</button>
	</section>
	</form>

	<section class="user-list section-wrapper">
		@if(empty(($viewModel->list)))
		<div class="container-fluid empty-list">
			<div class="row">
				<div class="col">查無符合資料</div>
			</div>
		</div>
		@else
		<div class="container-fluid list-data">
			<div class="row head">
				<div class="col col-1">#</div>
				<div class="col">AD帳號</div>
				<div class="col">顯示名稱</div>
				<div class="col">管理區域</div>
				<div class="col">權限身份</div>
				<div class="col col-action">操作</div>
			</div>
			@foreach($viewModel->list as $idx => $user)
			<div class="row">
				<div class="col col-1">{{ $idx + 1 }}</div>
				<div class="col">{{ $user['UserAd'] }}</div>
				<div class="col">{{ $user['UserDisplayName'] }}</div>
				<div class="col">{{ Area::getLabelByValue($user['UserAreaId']) }}</div>
				<div class="col">{{ $viewModel->getRoleById($user['UserRoleId']) }}</div>
				<div class="col col-action">
					@if($viewModel->canUpdate())
					<a href="{{ route('user.update', [$user['UserId']]) }}" class="btn btn-edit">
						<span class="material-symbols-outlined">edit</span>
					</a>
					@endif
					@if($viewModel->canDelete())
					<a href="{{ route('user.delete.post', [$user['UserId']]) }}" class="btn btn-del">
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