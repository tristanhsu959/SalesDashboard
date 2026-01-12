<!-- Menu component -->

<div class="aside menu">
	<div class="head">IT Portal</div>
	<div class="container-fluid">
		<ul>
			@foreach($menu as $item)
			<li class="{{ $item['style']['width'] }}">
				<a href="{{ $item['url'] }}" class="{{ $isActive($item['url']) }}">
					<span class="material-symbols-outlined">{{ $item['style']['icon'] }}</span>
					<span>{{ $item['name'] }}</span>
				</a>
			</li>
			@endforeach
			<li class="">
				<a href="" class="">
					<span class="material-symbols-outlined"></span>
					<span>UnDefined</span>
				</a>
			</li>
			<li class="">
				<a href="" class="">
					<span class="material-symbols-outlined"></span>
					<span>UnDefined</span>
				</a>
			</li>
			<li class="">
				<a href="" class="">
					<span class="material-symbols-outlined"></span>
					<span>UnDefined</span>
				</a>
			</li>
			<li class="">
				<a href="" class="">
					<span class="material-symbols-outlined"></span>
					<span>UnDefined</span>
				</a>
			</li>
		</ul>
	</div>
</div>