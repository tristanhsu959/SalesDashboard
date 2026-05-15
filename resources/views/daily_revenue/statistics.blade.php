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
	
		@if (! $viewModel->isDataEmpty())
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
		<div x-data='@json($viewModel->statistics)' class="statistics">
			<div class="tabs cyan-text">
				<a class="active" data-ui="#tab-area">區域彙總</a>
				<a data-ui="#tab-shop">店別明細</a>
			</div>
			
			<!-- 區域彙總 -->
			<div class="page padding active" id="tab-area">
				<section class="statistics-area">
					<table>
						<thead>
							<tr>
								<th x-text="area.header.areaName"></th>
								<th x-text="area.header.shopCount"></th>
								<template x-for="(date, dateKey) in area.header.dayAmount" :key="dateKey">
									<th x-text="date"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(areaData, areaId) in area.data" :key="areaId">
							<tr>
								<td x-text="areaData.areaName"></td>
								<td x-text="areaData.shopCount"></td>
								<template x-for="(date, dateKey) in area.header.dayAmount" :key="dateKey">
									<td x-text="'$'+ (areaData.dayAmount[date] || 0)"></td>
								</template>
							</tr>
							</template>
						</tbody>
					</table>
				</section>
			</div>
			
			<!-- 門店 -->
			<div class="page padding" id="tab-shop">
				<section class="statistics-shop scrollbar" :class="brandCode">
					<table class="stripes">
						<thead>
							<tr>
								<th x-text="shop.header.areaName"></th>
								<th x-text="shop.header.shopId"></th>
								<th x-text="shop.header.shopName"></th>
								<th x-text="shop.header.shopTypeName"></th>
								<template x-for="(date, dateKey) in shop.header.dayAmount" :key="dateKey">
									<th x-text="date"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(shopData, shopId) in shop.data" :key="shopId">
							<tr>
								<td x-text="shopData.areaName"></td>
								<td x-text="shopData.shopId"></td>
								<td x-text="shopData.shopName"></td>
								<td x-text="shopData.shopTypeName"></td>
								<template x-for="(date, dateKey) in shop.header.dayAmount" :key="dateKey">
									<td x-text="'$'+ (shopData.dayAmount[date] || 0)"></td>
								</template>
							</tr>
							</template>
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