@inject('service', 'App\Services\AppService')
@inject('viewHelper', 'App\ViewHelpers\MenuHelper')

<div class="aside menu">
	<div class="logo">
		<img src="{{ asset('images/logo.png') }}" />
	</div>
	<div class="container-fluid">
		@foreach($service->getAuthorizeMenu() as $key => $group)
		<div class="menu-group">
			<a href="#collapse{{ $key }}" class="list-title" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="collapse{{ $key }}">
				<span class="material-symbols-outlined {{ $viewHelper->getIconStyle($group['groupIcon']['filled']) }}">{{ $group['groupIcon']['name'] }}</span>{{ $group['groupName'] }}
			</a>
			<ul id="collapse{{ $key }}" class="list-group collapse">
				@foreach($group['items'] as $item)
				<li class="list-group-item"><a href="{{ url($item['url']) }}" class="{{ $viewHelper->getCurrentActionStyle($item['segmentCode']) }}">{{ $item['name'] }}</a></li>
				@endforeach
			</ul>
		</div>
		@endforeach
	</div>
</div>
