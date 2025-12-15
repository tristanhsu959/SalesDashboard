
<div class="aside menu dp-8">
	<div class="logo">
		<div class="logo-wrapper">
			<img src="{{ asset('images/logo.png') }}" />
		</div>
	</div>
	<div class="container-fluid">
		@foreach($appMenu->getMenu() as $key => $group)
		<div class="menu-group">
			<a href="#collapse{{ $key }}" class="list-title" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="collapse{{ $key }}">
				<span class="material-symbols-outlined {{ $group['groupIcon']['style'] }}">{{ $group['groupIcon']['name'] }}</span>{{ $group['groupName'] }}
			</a>
			<ul id="collapse{{ $key }}" class="list-group collapse">
				@foreach($group['items'] as $item)
				<li class="list-group-item"><a href="{{ url($item['url']) }}" class="{{ $appMenu->activeActionStyle($item['segmentCode']) }}">{{ $item['name'] }}</a></li>
				@endforeach
			</ul>
		</div>
		@endforeach
	</div>
</div>
