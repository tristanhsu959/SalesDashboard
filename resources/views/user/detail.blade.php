@extends('layouts.app')

@push('styles')
	<link href="{{ asset('styles/user/detail.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/user/detail.js') }}" defer></script>
@endpush

@section('content')

<form x-data='userForm(@json($viewModel->formData))' action="{{ $viewModel->getFormAction() }}" method="post" novalidate @submit.prevent="validate()">
	<input type="hidden" name="id" value="{{$viewModel->formData['id']}}" x-model="formData.id">
	@csrf
	
	<section class="user-data">
		<label x-show="formData.id" class="large-text">更新時間：{{$viewModel->get('formData.updateAt', '')}}</label>
		
		<div class="row">
			<div class="field label border field-dark-blue w25 prefix" :class="Helper.hasError(errors, 'ad')">
				<i class="small red-text">asterisk</i>
				<input type="text" name="adAccount" maxlength="15" required x-model="formData.ad" @input="errors.delete('ad')">
				<label>AD帳號</label>
			</div>
		</div>
		
		<div class="row">
			<div class="field label border field-dark-blue w25">
				<input type="text" name="displayName" maxlength="15" x-model="formData.name">
				<label>顯示名稱</label>
			</div>
		</div>
		
		<div class="field label suffix border field-dark-blue w25 prefix" :class="Helper.hasError(errors, 'roleId')">
			<i class="small red-text">asterisk</i>
			<select x-model="formData.roleId" name="roleId"  @change="errors.delete('roleId')">
				<option value="">請選擇</option>
				@foreach($viewModel->options['roleList'] as $id => $name)
					<option value="{{ $id }}">{{ $name }}</option>
				@endforeach
			</select>
			<label>身份</label>
			<i>arrow_drop_down</i>
		</div>
	
		<nav class="toolbar">
			<button type="submit" class="button btn-save btn-primary">{{ $viewModel->action->label()}}</button>
			<button @click="reset() "type="button" class="button btn-cancel border">重置</button>
		</nav>
	</section>
</form>

@endsection