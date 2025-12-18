@extends('layouts.master')

@push('styles')
	<link href="{{ asset('styles/user/detail.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/user/detail.js') }}" defer></script>
@endpush

@section('navHead', $viewModel->getBreadcrumb())

@section('navBack')
<a href="{{ route('user.list') }}" class="btn btn-return">
	<span class="material-symbols-outlined filled-icon">arrow_back</span>
	<span class="title">回列表</span>
</a>
@endsection

@section('content')
		
<form action="{{ $viewModel->getFormAction() }}" method="post" id="userForm">
<input type="hidden" value="{{ $viewModel->getUpdateUserId() }}" name="id">
@csrf

<section class="section-wrapper">
	<div class="section user-data">
		<div class="input-field field-cyan field required">
			<input type="text" class="form-control" id="adAccount" name="adAccount" value="{{  $viewModel->getUserAd() }}" maxlength="15" placeholder=" ">
			<label for="adAccount" class="form-label">AD帳號</label>
			<div class="input-hint">@8way.com.tw</div>
		</div>
		<div class="input-field field-cyan field">
			<input type="text" class="form-control" id="displayName" name="displayName" value="{{  $viewModel->getUserDisplayName() }}" maxlength="15" placeholder=" ">
			<label for="displayName" class="form-label">顯示名稱</label>
		</div>
	</div>
	<div class="section user-role required">
		<label class="title">所屬身份</label>
		@foreach($viewModel->roleList as $idx => $role)
		<label class="form-check-label" for="role{{$idx}}">
			<input class="form-check-input" type="radio" name="role" id="role{{$idx}}" value="{{ $role['RoleId'] }}" @checked($viewModel->checkedRole($role['RoleId'])) >
			{{ $role['RoleName'] }}
		</label>
		@endforeach
	</div>
	
	<div class="toolbar">
		<button type="button" class="btn btn-major btn-save">儲存</button>
		<button type="button" class="btn btn-red btn-reset">取消</button>
	</div>
</section>
</form>
@endsection()