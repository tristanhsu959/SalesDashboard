<!-- Menu component -->
{{--
<nav x-data='{menus:@json($menus), currentPath:@json($currentPath)}' class="m l max left menu">
	<header>
		<img src="{{ asset('images/logo.png') }}" />
	</header>
	
	<template x-for="(groups, key) in menus" :key="key">
	<div class="responsive">
		<span x-text="key"></span>
		<ul class="list">
			<template x-for="(item, index) in groups" :key="index">
			<li>
				<a :href="item.url" :class="currentPath.includes(item.url) ? 'active' : '' " class="responsive round">
					<i x-text="item.style.icon"></i>
					<span x-text="item.name"></span>
				</a>
			</li>
			</template>
		</ul>
	</div>
  	</template>
</nav>
--}}

<nav x-data='{menus:@json($menus), currentPath:@json($currentPath)}'  class="menu drawer1 left active">
	<header>
		<img src="{{ asset('images/logo.png') }}" />
	</header>
	
	<template x-for="(groups, key) in menus">
		<details x-data="{ isOpen: groups.some(item => currentPath.startsWith(item.url)) }" :open="isOpen" @toggle="isOpen = $el.open">
			<summary>
				<span x-text="key"></span>
				<i class="none" x-text="isOpen ? 'stat_minus_1':'chevron_forward'"></i>
			</summary>
			
			<template x-for="item in groups">
			<div class="item">
				<a :href="item.url" :class="[currentPath.includes(item.url) ? 'active' : '', item.style.color]" class="responsive">
					<i x-text="item.style.icon"></i>
					<span x-text="item.name" ></span>
				</a>
			</div>
			</template>
		</details>
	</template>
</nav>
