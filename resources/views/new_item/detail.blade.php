@extends('layouts.app')

@push('styles')
	<!--link href="{{ asset('styles/product/detail.css') }}" rel="stylesheet"-->
@endpush

@push('scripts')
    <script src="{{ asset('scripts/new_item/detail.js') }}" defer></script>
@endpush

@section('content')

<form x-data='newItemForm(@json($viewModel->formData), @json($viewModel->options))' 
	x-effect="productSettings;"
	action="{{ $viewModel->getFormAction() }}" method="post" novalidate @submit.prevent="validate()">
	<input type="hidden" name="id" value="{{$viewModel->formData['id']}}" x-model="formData.id">
	@csrf
	
	<section class="product-data container">
		
		<div class="field label suffix border field-dark-blue w25 prefix" :class="Helper.hasError(errors, 'brand')">
			<i class="small red-text">asterisk</i>
			<select x-model="formData.brand" name="brand" @change="errors.delete('brand'); productSettings; $nextTick(() => ui())">
				<option value="">請選擇</option>
				@foreach($viewModel->options['brands'] as $value => $brand)
					<option value="{{$value}}">{{$brand}}</option>
				@endforeach
			</select>
			<label>品牌</label>
			<i>arrow_drop_down</i>
		</div>
		
		<div class="field label suffix border field-dark-blue w30 prefix" :class="Helper.hasError(errors, 'brand')">
			<i class="small red-text">asterisk</i>
			<select x-model="formData.productId" x-init="$watch('productSettings', () => $nextTick(() => ui()))" name="productId" @change="errors.delete('productId');">
				<option value="">請選擇</option>
				<template x-for="item in productSettings">
					<option x-text="item.name" :value="item.id"></option>
				</template>
			</select>
			<label>產品料號</label>
			<i>arrow_drop_down</i>
		</div>
		
		<div class="field label border field-dark-blue w30 prefix" :class="Helper.hasError(errors, 'name')">
			<i class="small red-text">asterisk</i>
			<input type="text" name="name" maxlength="15" x-model="formData.name" @input="errors.delete('name')">
			<label>新品名稱</label>
		</div>
		
		 
		<div class="row">
			<label class="switch field-light-green">
				<input :checked="formData.status" @change="status = $el.checked ? 1 : 0" type="checkbox" name="status" value="1">
				<span></span>
				<i class="output" >啟用</i>
			</label>
		</div>
		
		<nav class="toolbar">
			<button type="submit" class="button btn-save btn-primary">{{ $viewModel->action->label()}}</button>
			<button @click="reset() "type="button" class="button btn-cancel border">重置</button>
		</nav>
	</section>
</form>

@endsection