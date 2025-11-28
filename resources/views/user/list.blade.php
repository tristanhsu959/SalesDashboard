@extends('layouts.master')

@push('styles')
    <link href="{{ asset('styles/user/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/user/list.js') }}" defer></script>
@endpush

@section('navHead', $viewModel->getBreadcrumb())

@section('navAction')
<a href="{{ route('user.create') }}" class="btn btn-create">
	<span class="material-symbols-outlined filled-icon">add</span>
	<span class="title">新增</span>
</a>
@endsection

@section('content')
{{--@if($status === TRUE)--}}
<section class="searchbar section-wrapper">
	<div class="input-field field-blue dark field">
		<input type="text" class="form-control valid" id="account" name="account" maxlength="15" placeholder=" ">
		<label for="account" class="form-label">AD帳號</label>
	</div>
	<div class="input-field field-blue dark field">
		<input type="text" class="form-control valid" id="displayName" name="displayName" maxlength="15" placeholder=" ">
		<label for="displayName" class="form-label">顯示名稱</label>
	</div>
	<div class="input-select field-blue dark field">
		<select class="form-select" id="area" name="area">
			<option value=""selected>請選擇</option>
			@foreach(Area::cases() as $area)
			<option value="{{ $area->value }}">{{ $area->label() }}</option>
			@endforeach
		</select>
		<label for="group" class="form-label">管理區域</label>
	</div>
	<button class="btn btn-search btn-info" type="button">
		<span class="material-symbols-outlined filled-icon">search</span>
	</button>
</section>

<section class="user-list section-wrapper">
	<div class="container-fluid">
		<div class="row head">
			<div class="col col-1">#</div>
			<div class="col">AD帳號</div>
			<div class="col">顯示名稱</div>
			<div class="col">管理區域</div>
			<div class="col col-3">E-Mail</div>
			<div class="col">權限身份</div>
			<div class="col col-action">操作</div>
		</div>
		<div class="row">
			<div class="col col-1">1</div>
			<div class="col">T2025001</div>
			<div class="col">Tomas</div>
			<div class="col">全區</div>
			<div class="col col-3">tristan.hsu@8way.com.tw</div>
			<div class="col">管理者</div>
			<div class="col col-action">
				<a href="" class="btn btn-edit">
					<span class="material-symbols-outlined">edit</span>
				</a>
				<a href="" class="btn btn-del">
					<span class="material-symbols-outlined">delete</span>
				</a>
			</div>
		</div>
		<div class="row">
			<div class="col col-1">1</div>
			<div class="col">T2025001</div>
			<div class="col">Tomas</div>
			<div class="col">北區</div>
			<div class="col col-3">tristan.hsu@8way.com.tw</div>
			<div class="col">使用者</div>
			<div class="col col-action">
				<a href="" class="btn btn-edit">
					<span class="material-symbols-outlined">edit</span>
				</a>
				<a href="" class="btn btn-del">
					<span class="material-symbols-outlined">delete</span>
				</a>
			</div>
		</div>
		<div class="row">
			<div class="col col-1">1</div>
			<div class="col">T2025001</div>
			<div class="col">Tomas</div>
			<div class="col">北區</div>
			<div class="col col-3">tristan.hsu@8way.com.tw</div>
			<div class="col">使用者</div>
			<div class="col col-action">
				<a href="" class="btn btn-edit">
					<span class="material-symbols-outlined">edit</span>
				</a>
				<a href="" class="btn btn-del">
					<span class="material-symbols-outlined">delete</span>
				</a>
			</div>
		</div>
		<div class="row">
			<div class="col col-1">1</div>
			<div class="col">T2025001</div>
			<div class="col">Tomas</div>
			<div class="col">北區</div>
			<div class="col col-3">tristan.hsu@8way.com.tw</div>
			<div class="col">使用者</div>
			<div class="col col-action">
				<a href="" class="btn btn-edit">
					<span class="material-symbols-outlined">edit</span>
				</a>
				<a href="" class="btn btn-del">
					<span class="material-symbols-outlined">delete</span>
				</a>
			</div>
		</div>
		<div class="row">
			<div class="col col-1">1</div>
			<div class="col">T2025001</div>
			<div class="col">Tomas</div>
			<div class="col">北區</div>
			<div class="col col-3">tristan.hsu@8way.com.tw</div>
			<div class="col">使用者</div>
			<div class="col col-action">
				<a href="" class="btn btn-edit">
					<span class="material-symbols-outlined">edit</span>
				</a>
				<a href="" class="btn btn-del">
					<span class="material-symbols-outlined">delete</span>
				</a>
			</div>
		</div>
		<div class="row">
			<div class="col col-1">1</div>
			<div class="col">T2025001</div>
			<div class="col">Tomas</div>
			<div class="col">北區</div>
			<div class="col col-3">tristan.hsu@8way.com.tw</div>
			<div class="col">使用者</div>
			<div class="col col-action">
				<a href="" class="btn btn-edit">
					<span class="material-symbols-outlined">edit</span>
				</a>
				<a href="" class="btn btn-del">
					<span class="material-symbols-outlined">delete</span>
				</a>
			</div>
		</div>
		<div class="row">
			<div class="col col-1">1</div>
			<div class="col">T2025001</div>
			<div class="col">Tomas</div>
			<div class="col">北區</div>
			<div class="col col-3">tristan.hsu@8way.com.tw</div>
			<div class="col">使用者</div>
			<div class="col col-action">
				<a href="" class="btn btn-edit">
					<span class="material-symbols-outlined">edit</span>
				</a>
				<a href="" class="btn btn-del">
					<span class="material-symbols-outlined">delete</span>
				</a>
			</div>
		</div>
		<div class="row">
			<div class="col col-1">1</div>
			<div class="col">T2025001</div>
			<div class="col">Tomas</div>
			<div class="col">北區</div>
			<div class="col col-3">tristan.hsu@8way.com.tw</div>
			<div class="col">使用者</div>
			<div class="col col-action">
				<a href="" class="btn btn-edit">
					<span class="material-symbols-outlined">edit</span>
				</a>
				<a href="" class="btn btn-del">
					<span class="material-symbols-outlined">delete</span>
				</a>
			</div>
		</div>
		<div class="row">
			<div class="col col-1">1</div>
			<div class="col">T2025001</div>
			<div class="col">Tomas</div>
			<div class="col">北區</div>
			<div class="col col-3">tristan.hsu@8way.com.tw</div>
			<div class="col">使用者</div>
			<div class="col col-action">
				<a href="" class="btn btn-edit">
					<span class="material-symbols-outlined">edit</span>
				</a>
				<a href="" class="btn btn-del">
					<span class="material-symbols-outlined">delete</span>
				</a>
			</div>
		</div>
		<div class="row">
			<div class="col col-1">1</div>
			<div class="col">T2025001</div>
			<div class="col">Tomas</div>
			<div class="col">北區</div>
			<div class="col col-3">tristan.hsu@8way.com.tw</div>
			<div class="col">使用者</div>
			<div class="col col-action">
				<a href="" class="btn btn-edit">
					<span class="material-symbols-outlined">edit</span>
				</a>
				<a href="" class="btn btn-del">
					<span class="material-symbols-outlined">delete</span>
				</a>
			</div>
		</div>
		<div class="row">
			<div class="col col-1">1</div>
			<div class="col">T2025001</div>
			<div class="col">Tomas</div>
			<div class="col">北區</div>
			<div class="col col-3">tristan.hsu@8way.com.tw</div>
			<div class="col">使用者</div>
			<div class="col col-action">
				<a href="" class="btn btn-edit">
					<span class="material-symbols-outlined">edit</span>
				</a>
				<a href="" class="btn btn-del">
					<span class="material-symbols-outlined">delete</span>
				</a>
			</div>
		</div>
		<div class="row">
			<div class="col col-1">1</div>
			<div class="col">T2025001</div>
			<div class="col">Tomas</div>
			<div class="col">北區</div>
			<div class="col col-3">tristan.hsu@8way.com.tw</div>
			<div class="col">使用者</div>
			<div class="col col-action">
				<a href="" class="btn btn-edit">
					<span class="material-symbols-outlined">edit</span>
				</a>
				<a href="" class="btn btn-del">
					<span class="material-symbols-outlined">delete</span>
				</a>
			</div>
		</div>
		<div class="row">
			<div class="col col-1">1</div>
			<div class="col">T2025001</div>
			<div class="col">Tomas</div>
			<div class="col">北區</div>
			<div class="col col-3">tristan.hsu@8way.com.tw</div>
			<div class="col">使用者</div>
			<div class="col col-action">
				<a href="" class="btn btn-edit">
					<span class="material-symbols-outlined">edit</span>
				</a>
				<a href="" class="btn btn-del">
					<span class="material-symbols-outlined">delete</span>
				</a>
			</div>
		</div>
	</div>
</section>
{{--@endif--}}
@endsection()