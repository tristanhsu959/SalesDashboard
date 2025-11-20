@inject('viewHelper', 'App\ViewHelpers\NewReleaseHelper')
@extends('layouts.master')

@push('styles')
	<link href="{{ asset('styles/role/role.css') }}" rel="stylesheet">
    <link href="{{ asset('styles/role/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/role/list.js') }}"></script>
@endpush

@section('navHead', '身份管理 | 列表')

@section('navAction')
<a href="{{ url('roles/create') }}" class="btn btn-create">
	<span class="material-symbols-outlined filled-icon">add</span>
	<span class="title">新增</span>
</a>
@endsection

@section('content')
{{--@if($status === TRUE)--}}

<section class="role-list section-wrapper">
	<div class="container-fluid">
		<div class="row hea1">
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
				<!--a href="" class="btn">編輯</a>
				<a href="" class="btn">刪除</a-->
			</div>
		</div>
		<div class="row">
			<div class="col">名稱</div>
			<div class="col">部門</div>
			<div class="col">職稱</div>
			<div class="col">權限群組</div>
			<div class="col col-3">
				<!--a href="" class="btn">編輯</a>
				<a href="" class="btn">刪除</a-->
			</div>
		</div>
	</div>
</section>
{{--@endif--}}
@endsection()