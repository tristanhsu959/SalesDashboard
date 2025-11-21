@inject('viewHelper', 'App\ViewHelpers\NewReleaseHelper')
@extends('layouts.master')

@push('styles')
    <link href="{{ asset('styles/user/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/user/list.js') }}"></script>
@endpush

@section('navHead', '帳號管理 | 列表')

@section('navAction')
<a href="{{ url('users/create') }}" class="btn btn-create">
	<span class="material-symbols-outlined filled-icon">add</span>
	<span class="title">新增</span>
</a>
@endsection

@section('content')
{{--@if($status === TRUE)--}}

<section class="user-list section-wrapper">
	<div class="container-fluid">
		<div class="row head">
			<div class="col col-1">#</div>
			<div class="col">AD帳號</div>
			<div class="col">顯示名稱</div>
			<div class="col">管理區域</div>
			<div class="col">權限身份</div>
			<div class="col col-action">操作</div>
		</div>
		<div class="row">
			<div class="col col-1">1</div>
			<div class="col">T2025001</div>
			<div class="col">Tomas</div>
			<div class="col">全區</div>
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