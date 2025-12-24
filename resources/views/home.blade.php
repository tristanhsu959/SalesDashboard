@extends('layouts.master')

@push('styles')
    <link href="{{ asset('styles/home/purchase.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src=""></script>
@endpush

@section('navHome')
<div class="navbar-home">
	<span class="sales">sales</span><span class="dashboard">Dashboard</span>
</div>
@endsection

@section('navAction', '')

@section('content')
<section class="section-wrapper">
	<div class="purchase dp-2">
		<h6>台北 屯山</h6>
		<ul class="list-group">
			<li class="list-group-item"><span>橙汁排骨</span><span>173 包</span></li>
			<li class="list-group-item"><span>紅燒牛肉調理包</span><span>386 組</span></li>
			<li class="list-group-item"><span>滷肉</span><span>108 包</span></li>
			<li class="list-group-item"><span>非基改雞蛋豆腐</span><span>202 個</span></li>
		</ul>
	</div>
	<div class="purchase dp-2">
		<h6>高雄 二崙</h6>
		<ul class="list-group">
			<li class="list-group-item"><span>橙汁排骨</span><span>173 包</span></li>
			<li class="list-group-item"><span>紅燒牛肉調理包</span><span>386 組</span></li>
			<li class="list-group-item"><span>滷肉</span><span>108 包</span></li>
			<li class="list-group-item"><span>非基改雞蛋豆腐</span><span>202 個</span></li>
		</ul>
	</div>
</section>
@endsection
