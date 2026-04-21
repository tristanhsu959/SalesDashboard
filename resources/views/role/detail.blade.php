@extends('layouts.app')
@use('App\Facades\AppManager')
@use('App\Enums\Area')
@use('App\Enums\RoleGroup')
@use('App\Enums\Brand')

@push('styles')
	<link href="{{ asset('styles/role/detail.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/role/detail.js') }}" defer></script>
@endpush

@section('content')
<form x-data='roleForm(@json($viewModel->formData), @json($viewModel->options))' action="{{ $viewModel->getFormAction() }}" method="post" novalidate @submit.prevent="validate()">
	<input type="hidden" name="id" :value="formData.id" x-model="formData.id">
	<input type="hidden" name="group" :value="formData.group">
	@csrf
	
	<section class="role-data container">
		<template x-if="formData.id > 0">
			<label class="large-text" x-text="`更新時間：${formData.updateAt}`"></label>
		</template>
		
		<div class="field label border field-purple w30 prefix" :class="Helper.hasError(errors, 'name')">
			<i class="small red-text">asterisk</i>
			<input type="text" name="name" maxlength="20" required x-model="formData.name" @input="errors.delete('name')">
			<label>身份名稱</label>
		</div>
		
		@foreach($viewModel->options['functions'] as $title => $groups)
		<fieldset class="role-permission field-purple fieldset required">
			<legend>{{$title}}</legend>
			<ul class="list border">
				@foreach($groups as $item)
				<li class="">
					<div class="max">
						<h6 class="small"></h6>
						<div>{{$item['name']}}</div>
					</div>
					<label class="switch field-dark-blue">
						<input x-model="formData.permission" type="checkbox" name="permission[]" value="{{$item['code']}}" @checked(in_array($item['code'], $viewModel->formData['permission']))>
						<span></span>
					</label>
				</li>
				@endforeach
			</ul>
		</fieldset>
		@endforeach
		
		<fieldset class="role-area field-blue fieldset required">
			<legend>管理區域</legend>
			<template x-for="(areaName, areaId) in options.areas" :key="areaId">
				<label class="form-check-label" :for="`area-${areaId}`">
					<input x-model="formData.area" class="form-check-input" type="checkbox" name="area[]" :id="`area-${areaId}`" :value="areaId">
					<span x-text="areaName"></span>
				</label>
			</template>
		</fieldset>
	
		<div class="space"></div>
		<nav class="toolbar">
			<button type="submit" class="button btn-save btn-primary slow-ripple">{{ $viewModel->action->label()}}</button>
			<button @click="reset() "type="button" class="button btn-cancel border slow-ripple" id="btnReset">重置</button>
		</nav>
	</section>
</form>

@endsection