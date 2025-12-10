@extends('layouts.master')

@push('styles')
    <link href="{{ asset('styles/signin/signin.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/signin/signin.js') }}" defer></script>
@endpush

@section('signin')

<div class="left-content">
	<img src="{{ asset('images/logo.svg') }}" />
	<span class="copyright">Bafang<i>&copy;</i>2025</span>
</div>

<div class="divider"></div>

<div class="right-content">
	<div class="head">
		<img src="{{ asset('images/microsoft_logo.png') }}" />
		<span class="title">Sign In</span>
		<h6>使用AD帳號登入至系統</h6>
	</div>
	
	<form action="{{ route('signin.post') }}" method="post" id="signinForm">
		@csrf
		<div class="input-field field-blue">
			<input type="text" class="form-control" id="adAccount" name="adAccount" value="{{ $viewModel->adAccount }}" maxlength="20" placeholder=" " required>
			<label for="adAccount" class="form-label">Account</label>
			<span class="domain-text">@8way.com.tw</span>
		</div>
		<div class="input-field field-blue">
			<input type="password" class="form-control" id="adPassword" name="adPassword" placeholder=" " maxlength="20" required>
			<label for="adPassword" class="form-label">Password</label>
		</div>
		<button id="btnSignin" type="button" class="btn btn-outline-danger">Sign In</button>
	</form>
</div>


@endsection()