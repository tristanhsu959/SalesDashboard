@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/signin.js') }}" defer></script>
@endpush

@section('content')

<div x-data="login(@js($viewModel->responseData()))" class="content-wrapper">
	<form :action="formData.formAction" method="post" class="row" novalidate @submit.prevent="validate()" >
		@csrf
		
		<div class="content-left">
			<img src="{{ asset('images/logo.svg') }}" />
			<span class="copyright">Bafang<i>&copy;</i>2025</span>
		</div>

		<div class="divider"> </div>

		<div class="content-right">
			<div class="header">
				<img src="{{ asset('images/microsoft_logo.png') }}" />
				<span class="title">Sign In</span>
				<h6>登入至DASHBOARD</h6>
			</div>
			<div class="field label border" :class="Helper.hasError(errors, 'account')">
				<input x-model="formData.account" type="text" name="account" maxlength="20" @input="errors.delete('account')">
				<label>Account</label>
				<!--span class="domain">@8way.com.tw</span-->
			</div>
			<div class="field label border" :class="Helper.hasError(errors, 'password')">
				<input x-model="formData.password" type="password" name="password" maxlength="20" @input="errors.delete('password')">
				<label>Password</label>
			</div>
			<div class="field label border captcha">
				<input type="text" name="captcha" maxlength="10">
				<label>Captcha</label>
			</div>
			<nav class="group split">
				<button type="submit" class="btn-red left-round max">
					<span>Sign In</span>
				</button>
				<button type="button" class="right-round square btn-cancel" @click="reset()">
					<i>close</i>
				</button>
			</nav>
			<nav>
				<div class="max"></div>
				<button type="button" class="right pink-text transparent" @click="ui('#forgetPwd')">忘記密碼</button>
			</nav>
		</div>
	</form>
</div>
	
<div x-data="changePassword(@js($viewModel->changePasswordData()))">
	<form :action="formData.formAction" method="post" class="row" novalidate @submit.prevent="validate()" >
		@csrf
		<dialog id="forgetPwd" class="modal">
			<h5>忘記密碼</h5>
			<div class="red-text small">系統將發送變更連結至此帳號信箱，請點擊連結進入密碼重設頁面</div>
			
			<div class="field label border" :class="Helper.hasError(errors, 'account')">
				<input x-model="formData.account" type="text" name="account" maxlength="20" @input="errors.delete('account')">
				<label>輸入登入帳號</label>
			</div>
			<nav class="group split">
				<button type="submit" class="light-blue left-round max">
					<span>送出</span>
				</button>
				<button type="button" class="right-round btn-cancel" @click="reset">
					<i>close</i>
				</button>
			</nav>
		</dialog>
	</form>
</div>
@endsection