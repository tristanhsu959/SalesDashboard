{{--@inject('viewHelper', 'App\ViewHelpers\AppHelper')--}}

<nav class="page navbar">
	<span class="navbar-head">
		<span class="material-symbols-outlined filled-icon">bubble_chart</span>
		@yield('navHead')
	</span>
	<span class="navbar-action">
		<a class="btn btn-profile" data-bs-toggle="offcanvas" href="#popup-profile" role="button" aria-controls="popup-profile">
			<span class="material-symbols-outlined">person</span>
		</a>
		@yield('navAction')
	</span>
</nav>

