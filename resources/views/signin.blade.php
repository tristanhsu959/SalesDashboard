@extends('layouts.app')

@push('scripts')
    <script src="{{ asset('scripts/signin.js') }}" defer></script>
@endpush

@section('content')

<form x-data='login(@json($viewModel->formData))' action="{{ route('signin.post') }}" method="post" class="row" novalidate @submit.prevent="validate()" >
	@csrf
	
	<div class="content-left">
		<img src="{{ asset('images/logo.svg') }}" />
		<span class="copyright">Bafang<i>&copy;</i>2025</span>
	</div>

	<div class="divider"> </div>

	<div class="content-right">
		<div class="head">
			<img src="{{ asset('images/microsoft_logo.png') }}" />
			<span class="title">Sign In</span>
			<h6>使用AD帳號登入至系統</h6>
		</div>
		<div class="field label border" :class="Helper.hasError(errors, 'account')">
			<input x-model="formData.account" type="text" name="account" maxlength="20" @input="errors.delete('account')">
			<label>Account</label>
			<span class="domain">@8way.com.tw</span>
		</div>
		<div class="field label border" :class="Helper.hasError(errors, 'password')">
			<input x-model="formData.password" type="password" name="password" maxlength="20" @input="errors.delete('password')">
			<label>Password</label>
		</div>
		<nav class="group split">
			<button type="submit" class="btn-red left-round max">
				<span>Sign In</span>
			</button>
			<button type="button" class="right-round square btn-cancel" @click="reset()">
				<i>close</i>
			</button>
		</nav>
	</div>
</form>

@endsection