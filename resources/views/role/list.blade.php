@inject('viewHelper', 'App\ViewHelpers\NewReleaseHelper')
@extends('layouts.master')

@push('styles')
	<link href="{{ asset('styles/role/role.css') }}" rel="stylesheet">
    <link href="{{ asset('styles/role/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/role/list.js') }}"></script>
@endpush

@section('content')

<nav class="page navbar">
	<span class="navbar-action">
		<span class="material-symbols-outlined filled-icon">bubble_chart</span>
		身份管理
	</span>
	<span class="navbar-operation">
		<a href="{{ url('roles/create') }}" class="btn btn-create w2">
			<span class="material-symbols-outlined filled-icon">add</span>
			<span class="title">新增</span>
		</a>
	</span>
</nav>

{{--@if($status === TRUE)--}}

<section class="role-list section-wrapper">
	<div class="container-fluid">
		<div class="row head">
			<div class="col">名稱</div>
			<div class="col">部門</div>
			<div class="col">職稱</div>
			<div class="col">權限群組</div>
			<div class="col col-3">操作</div>
		</div>
		<div class="row">
			<div class="col">名稱</div>
			<div class="col">部門</div>
			<div class="col">職稱</div>
			<div class="col">權限群組</div>
			<div class="col col-3">操作</div>
		</div>
		<div class="row">
			<div class="col">名稱</div>
			<div class="col">部門</div>
			<div class="col">職稱</div>
			<div class="col">權限群組</div>
			<div class="col col-3">
				<a href="" class="btn">編輯</a>
				<a href="" class="btn">刪除</a>
			</div>
		</div>
	</div>
</section>


{{--@endif--}}
@endsection()