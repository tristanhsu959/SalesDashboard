@extends('layouts.app')
@use('Illuminate\Support\Number')

@push('styles')
    <link href="{{ asset('styles/sales/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/sales/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Search panel -->
<form x-data='searchSales(@json($viewModel->search))' action="{{ $viewModel->getFormAction() }}" method="post" id="searchForm" class="no-margin" novalidate @submit.prevent="search()">
	@csrf

	<dialog id="searchPanel" class="right">
		<h5>查詢</h5>
		<div class="space"></div>
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'stDate')">
			<input type="date" name="searchStDate" maxlength="10" x-model="searchData.stDate" x-ref="searchStDate" @input="errors.delete('stDate')" :max="searchData.today">
			<label>開始日期</label>
		</div>
		
		<div class="space"></div>
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'endDate')">
			<input type="date" name="searchEndDate" maxlength="10" x-model="searchData.endDate" x-ref="searchEndDate" @input="errors.delete('endDate')" :max="searchData.today">
			<label>結束日期</label>
		</div>
		
		<div class="space"></div>
		<nav class="right-align group split">
			<button type="submit" class="btn-search left-round large"><i>search</i>查詢</button>
			<button @click="resetSearch()" type="button" class="btn-search-reset right-round square large"><i>backspace</i></button>
		</nav>
	</dialog>
</form>
<!-- Search panel end -->

<header class="page-nav">
	<nav>
		<button type="button" class="btn-show-search button circle" data-ui="#searchPanel"><i>search</i></button>
		
		@if ($viewModel->hasExportData())
		<a href="javascript:window.location.href='{{ $viewModel->getFormAction(TRUE) }}'" class="button circle red" type="button">
			<span class="material-symbols-outlined filled-icon">download_2</span>
		</a>
		<label class="switch icon">
			<input type="checkbox" x-model="$store.sales.showAmount">
			<span>
				<i>attach_money</i>
			</span>
		</label>
		@endif
	</nav>
</header>
	
@if($viewModel->status() === TRUE)	
	@if(isset($viewModel->statistics['brandId'])) <!-- loading or not -->
	<section class="sales-list container">
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
			<div class="page padding active scroll" id="tab-area">
				<section class="statistics-area">
					<table>
						<thead>
							<tr>
								<th>區域</th>
								<th>店家數</th>
								@foreach($viewModel->statistics['header'] as $productName)
								<th>{{$productName}}</th>
								@endforeach
							</tr>
						</thead>
						<tbody>
							@foreach($viewModel->statistics['area'] as $area)
							<tr>
								<td>{{ $area['areaName'] }}</td>
								<td>{{ data_get($area, 'shopCount', 0) }}</td>
								@foreach($viewModel->statistics['header'] as $productId => $productName)
								<td>
									<span x-show="!$store.sales.showAmount">{{ data_get($area, "products.$productId.totalQty", 0)}}</span>
									<span x-show="$store.sales.showAmount">{{ Number::currency(data_get($area, "products.$productId.totalAmount", 0), precision: 0)}}</span>
								</td>
								@endforeach
							</tr>
							@endforeach
						</tbody>
					</table>
				</section>
			</div>
			
			<div class="page padding" id="tab-shop">
				<section class="statistics-shop scrollbar">
					<table class="stripes odd-cyan">
						<thead>
							<tr>
								<th>區域</th>
								<th>門店代號</th>
								<th>門店名稱</th>
								@foreach($viewModel->statistics['header'] as $productName)
								<th>{{$productName}}</th>
								@endforeach
							</tr>
						</thead>
						<tbody>
							@foreach($viewModel->statistics['shop'] as $shopId => $shop)
							<tr>
								<th>{{ $shop['areaName'] }}</th>
								<th>{{ $shopId }}</th>
								<th>{{ $shop['shopName'] }}</th>
								@foreach($viewModel->statistics['header'] as $productId => $productName)
								<td>
									<span x-show="!$store.sales.showAmount">{{ data_get($shop, "products.$productId.totalQty", 0)}}</span>
									<span x-show="$store.sales.showAmount">{{ Number::currency(data_get($shop, "products.$productId.totalAmount", 0), precision: 0)}}</span>
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