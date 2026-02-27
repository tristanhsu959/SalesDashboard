
<header x-data='@json($initData)' class="orange">
	<nav>
		<a :href="backUrl" x-show="backUrl" class="button circle transparent">
			<i>arrow_back</i>
		</a>
		<h6 class="max small" x-html="breadcrumb.join('<i>chevron_right</i>')"></h6>
		<a :href="homeRoute" x-show="!isHome" class="button circle transparent">
			<i>home</i>
		</a>
		<button class="circle transparent" data-ui="#profile">
			<i>person</i>
		</button>
	</nav>
</header>