@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/forget_password.js') }}" defer></script>
@endpush

@section('content')

<div x-data="setPassword(@js($viewModel->responseData()))" class="content-wrapper">
	<form :action="formData.formAction" method="post" class="row" novalidate @submit.prevent="validate()" >
		@csrf
		<input type="hidden" name="id" :value="formData.id" />
		<input type="hidden" name="account" :value="formData.account" />
		<input type="hidden" name="name" :value="formData.name" />
		<div class="content-left">
			<img src="{{ asset('images/logo.svg') }}" />
			<span class="copyright">Bafang<i>&copy;</i>2025</span>
		</div>

		<div class="divider"> </div>

		<div class="content-right">
			<div class="header">
				<span class="title"><i>encrypted</i>密碼設定</span>
				<h6 class="red-text">請設定密碼後，重新登入</h6>
			</div>
			
			<div>
				<b x-text="formData.name"></b>
				<span x-text="`(${formData.account})`"></span>
			</div>
			
			<div class="field label border field-light-green" :class="Helper.hasError(errors, 'password')">
				<input x-model="formData.password" type="password" name="password" maxlength="20" @input="errors.delete('password')">
				<label>新密碼</label>
				<span class="red-text small-text">英文+數字六個字元以上</span>
			</div>
			<div class="field label border field-light-green" :class="Helper.hasError(errors, 'confirmPassword')">
				<input x-model="formData.confirmPassword" type="password" name="confirmPassword" maxlength="20" @input="errors.delete('confirmPassword')">
				<label>確認密碼</label>
				<span class="red-text small-text">再輸入一次新密碼</span>
			</div>
			
			<nav class="group split">
				<button type="submit" class="green left-round max">
					<span>儲存</span>
				</button>
				<button type="button" class="right-round square btn-cancel" @click="reset()">
					<i>close</i>
				</button>
			</nav>
		</div>
	</form>
</div>

@endsection

