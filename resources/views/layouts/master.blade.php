<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<title>八方雲集</title>
		
		<link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
		
		<!-- Styles & Font -->
		<link href="https://fonts.googleapis.com/css?family=Roboto|Orbitron&display=swap" rel="stylesheet" />
		<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
		<link href="{{ asset('styles/master.css') }}" rel="stylesheet" />
		@stack('styles')
		
		<!-- Scripts -->
		<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous" defer></script>
		<script src="https://code.jquery.com/ui/1.14.0/jquery-ui.min.js" integrity="sha256-Fb0zP4jE3JHqu+IBB9YktLcSjI1Zc6J2b6gTjB0LpoM=" crossorigin="anonymous" defer></script>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous" defer></script>
		<script src="{{ asset('scripts/master.js') }}" defer></script>
		@stack('scripts')
	</head>

	<body class="dark">
		@hasSection('signin')
			<div class='content-wrapper'>
				@yield('signin')
			</div>
		@else
			@include('layouts.master_menu')
		
			<div class='content-wrapper dark'>
				@include('layouts.master_actionbar')
				@hasSection('content')
					@yield('content')
				@endif
			</div>
			
			@include('layouts.master_profile')
			
		@endif
		
		@if(! empty($viewModel->msg) || ! empty(session('msg')))
		<div class="toast msg align-items-center" role="alert" aria-live="assertive" aria-atomic="true">
			<div class="d-flex">
				<div class="toast-body">
				{{ empty($viewModel->msg) ? session('msg') : $viewModel->msg }}
				</div>
				<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
			</div>
		</div>
		@endif
		
		@sectionMissing('signin')
			@include('layouts.master_dialog')
		@endif
	</body>
</html>
