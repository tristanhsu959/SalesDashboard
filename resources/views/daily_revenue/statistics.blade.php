@extends('layouts.app')
@use('Illuminate\Support\Number')

@push('styles')
    <link href="{{ asset('styles/daily_revenue/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/daily_revenue/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Search panel -->
<form x-data='search(@json($viewModel->search), @json($viewModel->options))' action="{{ $viewModel->getFormAction() }}" method="post" id="searchForm" class="no-margin" novalidate @submit.prevent="search()">
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
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'shopName')">
			<input type="text" name="searchShopName" maxlength="10" x-model="searchData.shopName" x-ref="searchShopName" @input="errors.delete('shopName')">
			<label>找店名</label>
		</div>
		
		<div class="field middle-align">
			<nav>
				<template x-for="(name, id) in options.shopType" :key="id">
					<label class="checkbox large">
						<input type="checkbox" name="searchShopType[]" :value="id" x-model="searchData.shopType">
						<span x-text="name"></span>
					</label>
				</template>
			</nav>
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
		<button type="button" class="btn-show-search button circle extend" data-ui="#searchPanel">
			<i>search</i>
			<span>查詢</span>
		</button>
	
		@if ($viewModel->hasExportData())
		<a href="javascript:window.location.href='{{ $viewModel->getFormAction(TRUE) }}'" class="button circle extend red" type="button">
			<i>download_2</i>
			<span>下載</span>
		</a>
		@endif
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
		<div x-data='statisticsData(@json($viewModel->statistics))' class="statistics">
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
								<template x-for="(headerName, headerKey) in areaData.header" :key="headerKey">
									<th x-text="headerName"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(row, areaKey) in areaData.data" :key="areaKey">
							<tr>
								<td x-text="row.areaName"></td>
								<td x-text="row.shopCount"></td>
								<template
								<td x-text="row.dayAmount"></td>
								<td>{{-- Number::currency(data_get($area, "dayAmount.$date", 0), precision: 0) --}}</td>
							</tr>
							</template>
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
								<th class="col-date">{{--$date--}}</th>
								
							</tr>
						</thead>
						<tbody>
							<tr>
								<th>{{-- $shop['areaName'] --}}</th>
								<th>{{-- $shopId --}}</th>
								<th>{{-- $shop['shopName'] --}}</th>
								<th>{{-- $shop['shopTypeName'] --}}</th>
								
								<td>
									{{-- Number::currency(data_get($shop, "dayAmount.$date", 0), precision: 0) --}}
								</td>
							</tr>
							
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