<!-- Menu component -->

<nav x-data='{menus:@json($menus), currentPath:@json($currentPath)}' class="menu drawer left active">
	<div class="logo">
		<div class="logo-wrapper">
			<img src="{{ asset('images/logo.png') }}" />
		</div>
	</div>
	
	<template x-for="(groups, key) in menus">
		<details :open="groups.some(item => currentPath.startsWith(item.url))">
			<summary>
				<i>folder</i>
				<span x-text="key"></span>
				<i class="none">arrow_drop_down</i>
			</summary>
			
			<template x-for="item in groups">
				<a :href="item.url" x-text="item.name" :class="currentPath.includes(item.url) ? 'active' : '' " class="responsive"></a>
			</template>
		</details>
	</template>
</nav>
