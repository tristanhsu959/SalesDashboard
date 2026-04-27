@extends('layouts.app')
@use('Illuminate\Support\Number')

@push('styles')
    <link href="{{ asset('styles/store/info.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/store/info.js') }}" defer></script>
@endpush

@section('content')

<header class="page-nav">
	<nav>
		
	{{--
		@if ($viewModel->hasExportData())
		<a href="javascript:window.location.href='{{ $viewModel->getFormAction(TRUE) }}'" class="button circle red" type="button">
			<span class="material-symbols-outlined filled-icon">download_2</span>
		</a>
		@endif
	--}}
	</nav>
</header>
	
@if($viewModel->status() === TRUE)	
	@if(isset($viewModel->statistics['brandId'])) <!-- loading or not -->
	<section class="new-release-list container">
		@if($viewModel->isDataEmpty())
		<article class="error-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
		@else
		<div class="statistics">
			<div class="tabs cyan-text">
				<a class="active" data-ui="#tab-area">區域彙總</a>
				<a data-ui="#tab-shop">店別明細</a>
			</div>
			
			<!-- 區域彙總 -->
			<div class="page padding active" id="tab-area">
				<section class="statistics-area">
					<table class="">
						<thead>
							<tr>
								<th>區域</th>
								<th>店家數</th>
								@foreach($viewModel->statistics['header'] as $date)
								<th>{{$date}}</th>
								@endforeach
							</tr>
						</thead>
						<tbody>
							@foreach($viewModel->statistics['area'] as $id => $area)
							<tr>
								<th>{{ data_get($area, 'areaName', '') }}</th>
								<th>{{ data_get($area, 'shopCount', 0) }}</th>
								@foreach($viewModel->statistics['header'] as $date)
								<td>{{ Number::currency(data_get($area, "dayAmount.$date", 0), precision: 0) }}</td>
								@endforeach
							</tr>
							@endforeach
						</tbody>
					</table>
				</section>
			</div>
			
			<!-- 門店 -->
			<div class="page padding" id="tab-shop">
				<section class="statistics-shop scrollbar {{$viewModel->getBrandCode()}}">
					<table class="stripes">
						<thead>
							<tr>
								<th>區域</th>
								<th>門店代號</th>
								<th>門店名稱</th>
								<th>類型</th>
								@foreach($viewModel->statistics['header'] as $date)
								<th class="col-date">{{$date}}</th>
								@endforeach
							</tr>
						</thead>
						<tbody>
							@foreach($viewModel->statistics['shop'] as $shopId => $shop)
							<tr>
								<th>{{ $shop['areaName'] }}</th>
								<th>{{ $shopId }}</th>
								<th>{{ $shop['shopName'] }}</th>
								<th>{{ $shop['shopTypeName'] }}</th>
								
								@foreach($viewModel->statistics['header'] as $date)
								<td>
									{{ Number::currency(data_get($shop, "dayAmount.$date", 0), precision: 0) }}
								</td>
								@endforeach
							</tr>
							@endforeach
						</tbody>
					</table>
				</section>
			</div>
			
		</div>
		@endif
	</section>
	@else
	<section class="container">
		<pre><i>arrow_warm_up</i>點擊查詢按鈕執行查詢</pre>
	</section>
	@endif
	
@endif

@endsection