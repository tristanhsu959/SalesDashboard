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
		<div class="row head">
			<div class="col col-1">#</div>
			<div class="col">身份</div>
			<div class="col">權限群組</div>
			<div class="col col-action">操作</div>
		</div>
		<div class="row">
			<div class="col col-1">1</div>
			<div class="col">經理</div>
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
			<div class="col col-1">2</div>
			<div class="col col-1">經理</div>
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
			<div class="col col-1">3</div>
			<div class="col col-1">經理</div>
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