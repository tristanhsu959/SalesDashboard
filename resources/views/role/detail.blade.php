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
<form x-data='roleForm(@json($viewModel->formData))' action="{{ $viewModel->getFormAction() }}" method="post" novalidate @submit.prevent="validate()">
	<input type="hidden" name="id" value="{{$viewModel->formData['id']}}" x-model="formData.id">
	<input type="hidden" name="group" value="{{$viewModel->formData['group']}}">
	@csrf
	
	<section class="role-data">
		<label x-show="formData.id" class="large-text">更新時間：{{$viewModel->get('formData.updateAt', '')}}</label>
		
		<div class="row">
			<div class="field label border field-purple" :class="Helper.hasError(errors, 'name')">
				<input type="text" name="name" maxlength="10" required x-model="formData.name" @input="errors.delete('name')">
				<label>身份名稱</label>
			</div>
		</div>
		
		@foreach($viewModel->options['functions'] as $key => $groups)
		<fieldset class="role-permission field-purple fieldset required">
			<legend>{{AppManager::getMenuGroupName($key)}}</legend>
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
			@foreach($viewModel->options['areas'] as $idx => $area)
			<label class="form-check-label" for="area-{{$idx}}">
				<input x-model="formData.area" class="form-check-input" type="checkbox" name="area[]" id="area-{{$idx}}" value="{{ $area->value }}"  @checked(in_array($area->value, $viewModel->formData['area']))>
				{{ $area->label() }}
			</label>
			@endforeach
		</fieldset>
	
		<nav class="toolbar">
			<button type="submit" class="button btn-save btn-primary">{{ $viewModel->action->label()}}</button>
			<button @click="reset() "type="button" class="button btn-cancel border" id="btnReset">重置</button>
		</nav>
	</section>
</form>

@endsection()