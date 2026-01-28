@extends('layouts.master')

@push('styles')
    <link href="{{ asset('styles/lunar.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/lunar.js') }}" defer></script>
@endpush

<!-- 先用signin避免吃到共用layout -->
@section('signin')

<div class="lunar-warpper">
	<section class="lunar-cars">
		<h4>春節預購車次設定</h4>
		<div class="group">
			<div class="header"><span>除夕</span>2026-02-16</div>
			<a href="{{ route('lunar.assign', ['date'=>'2026-02-16'])}}" class="btn btn-assign" data-bs-toggle="tooltip" data-bs-placement="top" title="設定新車次"><span class="material-symbols-outlined">settings</span></a>
			<a href="{{ route('lunar.restore', ['2026-02-16'])}}" class="btn btn-restore" data-bs-toggle="tooltip" data-bs-placement="top" title="恢復原車次"><span class="material-symbols-outlined">reset_settings</span></a>
		</div>
		<div class="group">
			<div class="header"><span>初二</span>2026-02-18</div>
			<a href="{{ route('lunar.assign', ['2026-02-18'])}}" class="btn btn-assign" data-bs-toggle="tooltip" data-bs-placement="top" title="設定新車次"><span class="material-symbols-outlined">settings</span></a>
			<a href="{{ route('lunar.restore', ['2026-02-18'])}}" class="btn btn-restore" data-bs-toggle="tooltip" data-bs-placement="top" title="恢復原車次"><span class="material-symbols-outlined">reset_settings</span></a>
		</div>
		<div class="group">
			<div class="header"><span>初三</span>2026-02-19</div>
			<a href="{{ route('lunar.assign', ['2026-02-19'])}}" class="btn btn-assign" data-bs-toggle="tooltip" data-bs-placement="top" title="設定新車次"><span class="material-symbols-outlined">settings</span></a>
			<a href="{{ route('lunar.restore', ['2026-02-19'])}}" class="btn btn-restore" data-bs-toggle="tooltip" data-bs-placement="top" title="恢復原車次"><span class="material-symbols-outlined">reset_settings</span></a>
		</div>
		<div class="group">
			<div class="header"><span>初四</span>2026-02-20</div>
			<a href="{{ route('lunar.assign', ['2026-02-20'])}}" class="btn btn-assign" data-bs-toggle="tooltip" data-bs-placement="top" title="設定新車次"><span class="material-symbols-outlined">settings</span></a>
			<a href="{{ route('lunar.restore', ['2026-02-20'])}}" class="btn btn-restore" data-bs-toggle="tooltip" data-bs-placement="top" title="恢復原車次"><span class="material-symbols-outlined">reset_settings</span></a>
		</div>
	</section>
	@if($viewModel->status())
	<section class="lunar-cars-result">
		<h6>台北目前車次設定<span class="badge bg-primary">{{ count($viewModel->settings['tp']) }}</span></h6>
		<ul class="list-tp list-group">
			@foreach($viewModel->settings['tp'] as $setting)
			<li class="list-group-item">
				<div class="shop"><span>{{ $setting['AccNo'] }}</span><span>{{ $setting['AccName'] }}</span></div>
				<div class="car-no">{{ $setting['CarNo'] }}</div>
			</li>
			@endforeach
		</ul>
	</section>
	<section class="lunar-cars-result">
		<h6>屯山目前車次設定<span class="badge bg-success">{{ count($viewModel->settings['ts']) }}</span></h6>
		<ul class="list-tp list-group">
			@foreach($viewModel->settings['ts'] as $setting)
			<li class="list-group-item">
				<div class="shop"><span>{{ $setting['AccNo'] }}</span><span>{{ $setting['AccName'] }}</span></div>
				<div class="car-no">{{ $setting['CarNo'] }}</div>
			</li>
			@endforeach
		</ul>
	</section>
	@endif
</div>

@endsection()