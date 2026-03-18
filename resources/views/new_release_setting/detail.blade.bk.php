@extends('layouts.app')

@push('styles')
	<!--link href="{{ asset('styles/product/detail.css') }}" rel="stylesheet"-->
@endpush

@push('scripts')
    <script src="{{ asset('scripts/new_release_setting/detail.js') }}" defer></script>
@endpush

@section('content')

<form x-data='releaseSettingForm(@json($viewModel->formData), @json($viewModel->options))' 
	action="{{ $viewModel->getFormAction() }}" method="post" novalidate @submit.prevent="validate()">
	<input type="hidden" name="id" value="{{$viewModel->formData['id']}}" x-model="formData.id">
	@csrf
	
	<section class="product-data container">
		<label x-show="formData.id" class="large-text" x-text="'更新時間：' + formData.updateAt"></label>
		
		<div class="field label suffix border field-dark-blue w20 prefix" :class="Helper.hasError(errors, 'brandId')">
			<i class="small red-text">asterisk</i>
			<select x-model="formData.brandId" name="brandId" @change="errors.delete('brandId'); updateProducts();">
				<template x-for="(name, id) in options.brands" :key="id">
					<option :value="id" x-text="name" :selected="formData.brandId == id"></option>
				</template>
			</select>
			<label>品牌</label>
			<i>arrow_drop_down</i>
		</div>
		
		<div class="field label border field-dark-blue w30 prefix" :class="Helper.hasError(errors, 'name')">
			<i class="small red-text">asterisk</i>
			<input type="text" name="name" maxlength="20" x-model="formData.name" @input="errors.delete('name')">
			<label>新品名稱</label>
		</div>
		
		<div class="field label border field-dark-blue w30 prefix" :class="Helper.hasError(errors, 'saleDate')">
			<i class="small red-text">asterisk</i>
			<input type="date" name="saleDate" maxlength="15" x-model="formData.saleDate" @input="errors.delete('saleDate')">
			<label>發售日</label>
		</div>
		
		<div class="field label suffix border field-dark-blue w30 prefix" :class="Helper.hasError(errors, 'productId')">
			<i class="small red-text">asterisk</i>
			<select x-model="formData.productId" name="productId" @change="errors.delete('productId');">
				<option value="">請選擇</option>
				<template x-for="(item, idx) in products" :key="idx">
					<option x-text="item.productName" :value="item.productId" :selected="formData.productId == item.productId"></option>
				</template>
			</select>
			<label>對應產品料號</label>
			<i>arrow_drop_down</i>
		</div>
		
		<div class="field label border field-dark-blue w30">
			<textarea x-model="formData.tasteKeyWord" name="tasteKeyWord" rows="5" placeholder=" "></textarea>
			<label>加值關鍵字</label>
			<output class="red-text">每個關鍵字以換行分隔</output>
  		</div>
		
		<div class="row">
			<label class="switch field-light-green">
				<input x-model="formData.status" :checked="formData.status == 1" @change="formData.status = $el.checked ? 1 : 0" type="checkbox" name="status" value="1">
				<span></span>
				<i class="output">啟用</i>
			</label>
		</div>
		
		<nav class="toolbar">
			<button type="submit" class="button btn-save btn-primary">{{ $viewModel->action->label()}}</button>
			<button @click="reset() "type="button" class="button btn-cancel border">重置</button>
		</nav>
	</section>
</form>

@endsection