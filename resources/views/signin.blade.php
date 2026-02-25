@extends('layouts.app')

@push('scripts')
    <!--script src="{{ asset('scripts/signin/signin.js') }}" defer></script-->
@endpush

@section('content')

<form x-data action="{{ route('signin.post') }}" method="post" class="row" novalidate @submit.prevent="submit" >
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
		<div class="field label border">
			<input type="text" name="adAccount" value="{{ $viewModel->adAccount }}" maxlength="20">
			<label>Account</label>
			<span class="domain">@8way.com.tw</span>
		</div>
		<div class="field label border">
			<input type="text" name="adPassword" maxlength="20">
			<label>Password</label>
		</div>
		<nav class="group split">
			<button type="submit" class="btn-signin left-round max">
				<span>Sign In</span>
			</button>
			<button type="button" class="right-round square btn-cancel">
				<i>close</i>
			</button>
		</nav>
	</div>
</form>

@endsection()