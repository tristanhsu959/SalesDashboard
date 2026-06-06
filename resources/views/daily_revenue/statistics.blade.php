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

<dialog x-data="search(@js($viewModel->searchFormData()))" id="searchPanel" class="right">
	<form :action="searchData.formAction" method="post" id="searchForm" novalidate @submit.prevent="search()">
	@csrf
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
	</form>
</dialog>

<!-- Search panel end -->
<div x-data="{response:@js($viewModel->responseData())}" class="content-wrapper">
	<header class="page-nav">
		<nav>
			<button type="button" class="btn-show-search button circle extend" data-ui="#searchPanel">
				<i>search</i>
				<span>查詢</span>
			</button>
		
			<template x-if="response.exportAction">
				<a :href="`javascript:window.location.href='${response.exportAction}'`" class="button circle extend red" type="button">
					<i>download_2</i>
					<span>下載</span>
				</a>
			</template>
		</nav>
	</header>
	
	<template x-if="response.status && !response.hasResult">
		<!-- Loading -->
		<section class="container">
			<pre><i>arrow_warm_up</i>點擊查詢按鈕執行查詢</pre>
		</section>
	</template>
	
	<template x-if="!response.status">
		<section class="container">
			<article class="error-container border">
				<div class="row">
					<i>error</i><div class="max">查詢時發生錯誤，請重新查詢</div>
				</div>
			</article>
		</section>
	</template>
	
	<template x-if="response.status && response.hasResult">
		<section x-data="{statistics:@js($viewModel->statisticsData())}" class="new-release-list container">
			<article x-show="!statistics.exportToken" class="secondary-container border">
				<div class="row">
					<i>info</i><div class="max">查無符合資料</div>
				</div>
			</article>
			
			<div x-show="statistics.exportToken" class="statistics">
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
									<th x-text="statistics.area.header.areaName"></th>
									<th x-text="statistics.area.header.shopCount"></th>
									<template x-for="(date, dateKey) in statistics.area.header.dayAmount" :key="dateKey">
										<th x-text="date"></th>
									</template>
								</tr>
							</thead>
							<tbody>
								<template x-for="(areaData, areaId) in statistics.area.data" :key="areaId">
								<tr>
									<td x-text="areaData.areaName"></td>
									<td x-text="areaData.shopCount"></td>
									<template x-for="(date, dateKey) in statistics.area.header.dayAmount" :key="dateKey">
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
					<section class="statistics-store scrollbar" :class="statistics.brandCode">
						<table class="stripes">
							<thead>
								<tr>
									<th x-text="statistics.shop.header.areaName"></th>
									<th x-text="statistics.shop.header.shopId"></th>
									<th x-text="statistics.shop.header.shopName"></th>
									<th x-text="statistics.shop.header.shopTypeName"></th>
									<template x-for="(date, dateKey) in statistics.shop.header.dayAmount" :key="dateKey">
										<th x-text="date"></th>
									</template>
								</tr>
							</thead>
							<tbody>
								<template x-for="(shopData, shopId) in statistics.shop.data" :key="shopId">
								<tr>
									<td x-text="shopData.areaName"></td>
									<td x-text="shopData.shopId"></td>
									<td x-text="shopData.shopName"></td>
									<td x-text="shopData.shopTypeName"></td>
									<template x-for="(date, dateKey) in statistics.shop.header.dayAmount" :key="dateKey">
										<td x-text="'$'+ (shopData.dayAmount[date] || 0)"></td>
									</template>
								</tr>
								</template>
							</tbody>
						</table>
					</section>
				</div>
			</div>
	</section>
</div>
@endsection