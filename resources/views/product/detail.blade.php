@extends('layouts.app')

@push('styles')
	<!--link href="{{ asset('styles/product/detail.css') }}" rel="stylesheet"-->
@endpush

@push('scripts')
    <script src="{{ asset('scripts/product/detail.js') }}" defer></script>
@endpush

@section('content')

<form x-data='productForm(@json($viewModel->formData))' action="{{ $viewModel->getFormAction() }}" method="post" novalidate @submit.prevent="validate()">
	<input type="hidden" name="id" value="{{$viewModel->formData['id']}}" x-model="formData.id">
	@csrf
	
	<section class="product-data container">
		
		<div class="field label suffix border field-dark-blue w25 prefix" :class="Helper.hasError(errors, 'brand')">
			<i class="small red-text">asterisk</i>
			<select x-model="formData.brand" name="brand" @change="initErpNoInput">
				<option value="">請選擇</option>
				@foreach($viewModel->options['brands'] as $brand)
					<option value="{{ $brand->value }}">{{ $brand->label() }}</option>
				@endforeach
			</select>
			<label>品牌</label>
			<i>arrow_drop_down</i>
		</div>
		
		<div class="field label border field-dark-blue w30 prefix" :class="Helper.hasError(errors, 'name')">
			<i class="small red-text">asterisk</i>
			<input type="text" name="name" maxlength="15" x-model="formData.name" @input="errors.delete('name')">
			<label>產品名稱</label>
		</div>
		 
		<div class="row top-align">
			<div class="field label border field-dark-blue w30 prefix" :class="Helper.hasError(errors, 'primaryNo')">
				<i class="small red-text">asterisk</i>
				<textarea x-model="formData.primaryNo" @input="errors.delete('primaryNo')" name="primaryNo" rows="10" placeholder=" "></textarea>
				<label>主要ERP No</label>
				<output class="red-text">每個序號以換行分隔</output>
  			</div>
			<div x-show="hasSecondaryNo" class="field label border field-dark-blue w30">
				<textarea x-model="formData.secondaryNo" :disabled="!hasSecondaryNo" name="secondaryNo" rows="10" placeholder=" "></textarea>
				<label>複合店ERP No</label>
				<output class="red-text">每個序號以換行分隔</output>
  			</div>
			<div class="field label border field-dark-blue w30">
				<textarea x-model="formData.tasteNo" name="tasteNo" rows="10" placeholder=" "></textarea>
				<label>加值序號</label>
				<output class="red-text">POS SALE011欄位，格式：TasteId:TasteSno<br/>每個序號以換行分隔</output>
  			</div>
		</div>
		<div class="row">
			<label class="switch field-light-green">
				<input x-model="formData.status" type="checkbox" name="status" value="1">
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