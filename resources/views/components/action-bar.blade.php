
<nav class="page navbar">
	<span class="navbar-head">
		<a href="{{ $getRoute() }}" class="btn btn-return {{ $active() }}">
			<span class="material-symbols-outlined filled-icon">arrow_back</span>
			<span class="title">回列表</span>
		</a>
		{!! $renderBreadcrumb !!}
	</span>
	<span class="navbar-action">
		<!--a class="btn btn-menu" href="javascript:void()" role="button">
			<span class="material-symbols-outlined">menu</span>
		</a-->
		<a class="btn btn-home" href="{{ route('home') }}" role="button">
			<span class="material-symbols-outlined">home</span>
		</a>
		<a class="btn btn-profile" data-bs-toggle="offcanvas" href="#popup-profile" role="button" aria-controls="popup-profile">
			<span class="material-symbols-outlined">person</span>
		</a>
		@yield('navAction')
	</span>
</nav>