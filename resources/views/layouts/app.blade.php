@use('App\Facades\AppManager')

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<title>八方雲集{{ empty(env('APP_ENV_HEAD')) ? '': '-' . env('APP_ENV_HEAD')}}</title>
		
		<link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
		
		<!-- Styles & Font -->
		<link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet" />
		<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400..900&display=swap" rel="stylesheet">
		<link href="https://fonts.googleapis.com/css2?family=Poiret+One&display=swap" rel="stylesheet">
		<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
		<link href="https://cdn.jsdelivr.net/npm/beercss@4.0.7/dist/cdn/beer.min.css" rel="stylesheet">
		<link href="{{ asset('styles/include.css') }}" rel="stylesheet" />
		<link href="{{ asset('styles/app.css') }}" rel="stylesheet" />
		@stack('styles')
		
		<!-- Scripts -->
		<script type="module" src="https://cdn.jsdelivr.net/npm/beercss@4.0.7/dist/cdn/beer.min.js" defer></script>
		<script type="module" src="https://cdn.jsdelivr.net/npm/material-dynamic-colors@1.1.4/dist/cdn/material-dynamic-colors.min.js" defer></script>
		<script src="{{ asset('scripts/util.js') }}" defer></script>
		<script src="{{ asset('scripts/helper.js') }}" defer></script>
		<script src="{{ asset('scripts/app.js') }}" defer></script>
		@stack('scripts')
		@vite(['resources/js/app.js'])
	</head>

	<body class="responsive">
		@if(AppManager::hasAuth())
			<x-menu />
		@endif
		
		<main x-data="{'hasAuth': {{ AppManager::hasAuth() ? 'true' : 'false' }}}" :class="hasAuth ? 'app':'signin'" class="responsive">
			@if(AppManager::hasAuth())
				<x-action-bar :isHome="$viewModel->isHome()" :breadcrumb="$viewModel->breadcrumb()" :routeName="$viewModel->backRoute()"/>
			@endif
			
			@yield('content')
		</main>
		
		@if(AppManager::hasAuth())
			<x-profile />
		@endif
		
		@include('layouts._dialog')
		@include('layouts._toast')
	</body>
</html>
