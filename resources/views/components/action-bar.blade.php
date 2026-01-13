
<nav class="page navbar">
	@if($isHome())
	<div class="navbar-home">
		<span class="sales">sales</span><span class="dashboard">Dashboard</span>
	</div>
	@else
	<span class="navbar-head">
		@if(! empty($getRoute()))
		<a href="{{ $getRoute() }}" class="btn btn-return {{ $active() }}">
			<span class="material-symbols-outlined filled-icon">arrow_back</span>
			<span class="title">回列表</span>
		</a>
		@endif
		
		{!! $renderBreadcrumb !!}
	</span>
	@endif
	
	<span class="navbar-action">
		@if(! $isHome())
		<a class="btn btn-home" href="{{ route('home') }}" role="button">
			<span class="material-symbols-outlined">home</span>
		</a>
		@endif
		
		<a class="btn btn-profile" data-bs-toggle="offcanvas" href="#popup-profile" role="button" aria-controls="popup-profile">
			<span class="material-symbols-outlined">person</span>
		</a>
	</span>
</nav>
<div id="loading" class="loading-wrapper">
	<div class="loading-bar"></div>
</div>
