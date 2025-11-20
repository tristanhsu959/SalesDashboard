@inject('viewHelper', 'App\ViewHelpers\NewReleaseHelper')
@extends('layouts.master')

@push('styles')
    <link href="{{ asset('styles/new_release/new_release.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/new_release/new_release.js') }}"></script>
@endpush

@section('content')

<nav class="page navbar {{ $viewHelper->getHeadStyle($segment) }}">
	<span class="navbar-brand"><span class="material-symbols-outlined filled-icon">bubble_chart</span>{{ $data['productName'] }}</span>
	<span class="navbar-info">
		<div>發售日</div>
		<div>{{ $data['saleDate'] }}</div>
	</span>
</nav>

@if($status === TRUE)
<div class='caption'>區域彙總</div>
<section class="statistics-area section-wrapper container-fluid">
	<div class="row">
		<div class="col">區域</div>
		<div class="col">店家數</div>
		<div class="col">區域銷售總量</div>
		<div class="col">區域平均日銷售量</div>
		<div class="col">區域每店平均銷量</div>
		<div class="col">區域每店平均日銷量</div>
    </div>
	
	@foreach($data['area'] as $name => $area)
	<div class="row">
		<div class="col">{{ $name == 'total' ? '全區合計' : $name }}</div>
		<div class="col">{{ $area['shopCount'] }}</div>
		<div class="col">{{ $area['totalQty'] }}</div>
		<div class="col">{{ $area['avgDayQty'] }}</div>
		<div class="col">{{ $area['avgShopQty'] }}</div>
		<div class="col">{{ $area['avgDayShopQty'] }}</div>
    </div>
	@endforeach
</section>

<div class='caption'>店別明細</div>
<section class="statistics-shop section-wrapper scrollbar">
	<table class="table table-striped">
		<thead>
			<tr>
				<th>區域</th>
				<th>門店代號</th>
				<th>門店名稱</th>
				@foreach($viewHelper->getDateRangeList($data['startDate'], $data['endDate']) as $date)
				<th class="col-date">
					<span>{{ Str::before($date, '-') }}</span>
					<span>{{ Str::after($date, '-') }}</span>
				</th>
				@endforeach
				<th>銷售總量</th>
				<th>平均銷售數量</th>
			</tr>
		</thead>
		<tbody>
			@foreach($data['shop'] as $shop)
			<tr>
				<th>{{ $shop['area'] }}</th>
				<th>{{ $shop['shopId'] }}</th>
				<th>{{ $shop['shopName'] }}</th>
				
				@foreach($viewHelper->getDateRangeList($data['startDate'], $data['endDate']) as $date)
				<td>
					{{ data_get($shop, "dayQty.$date", 0) }}
				</td>
				@endforeach
				
				<td>{{ $shop['totalQty'] }}</td>
				<td>{{ $shop['totalAvg'] }}</td>
			</tr>
			@endforeach
		</tbody>
	</table>
</section>

<section class="statistics-ranking section-wrapper container-fluid">
	<div class="card ranking-top">
		<div class="card-body">
			<h5 class="card-title">當日銷售前10名</h5>
			<ul class="list-group">
			@foreach($data['top'] as $ranking => $shopGroup)
				@foreach($shopGroup as $shop)
				<li class="list-group-item">
					<div class="ranking">{{ $ranking + 1 }}</div>
					<div class="info">
						{{ $shop['area'] }}
						<div class="name">{{ $shop['shopName'] }}</div>
						<span>{{ $shop['shopId'] }}</span>
					</div>
					<span class="badge rounded-pill">{{ $shop['todayQty'] }}</span>
				</li>
				@endforeach
			@endforeach
			</ul>
		</div>
	</div>
	<div class="card ranking-last">
		<div class="card-body">
			<h5 class="card-title">當日銷售後10名</h5>
			<ul class="list-group">
			@foreach($data['last'] as $ranking => $shopGroup)
				@foreach($shopGroup as $shop)
				<li class="list-group-item">
					<div class="ranking">{{ $ranking + 1 }}</div>
					<div class="info">
						{{ $shop['area'] }}
						<div class="name">{{ $shop['shopName'] }}</div>
						<span>{{ $shop['shopId'] }}</span>
					</div>
					<span class="badge rounded-pill">{{ $shop['todayQty'] }}</span>
				</li>
				@endforeach
			@endforeach	
			</ul>
		</div>
	</div>
</section>
@endif
@endsection()