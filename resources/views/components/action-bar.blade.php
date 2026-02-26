
<header x-data='{breadcrumb:@json($breadcrumb), backRoute:@json($backRoute), isHome:@json($isHome)}' class="tertiary-container">
	<nav>
		<a :href="backRoute" x-show="backRoute ? true : false" class="button circle transparent">
			<i>arrow_back</i>
		</a>
		<h6 class="max">Headline</h6>
		<a href="{{ route('home') }}" x-show="!isHome" class="button circle transparent">
			<i>home</i>
		</a>
		<a :href="{{ route('home') }}" class="button circle transparent">
			<i>person</i>
		</a>
	</nav>
</header>