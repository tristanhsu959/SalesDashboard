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
<dialog x-data="searchSales(@js($viewModel->searchFormData()))" id="searchPanel" class="right">
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
		<div class="field label suffix round border field-light-blue" :class="Helper.hasError(errors, 'category')">
			<select x-model="searchData.category" name="searchCategory" @change="searchData.productIds = []">
				<option value="">請選擇</option>
				<template x-for="(name, catId) in options.category" :key="catId">
					<option x-text="name" :value="catId" :selected="searchData.category == catId"></option>
				</template>
			</select>
			<label>類別</label>
			<i>arrow_drop_down</i>
		</div>
		
		<template x-for="(products, catId) in options.products" :key="catId">
			<fieldset x-show="searchData.category == catId" class="field-dark-blue fieldset">
				<legend><i class="small red-text">asterisk</i><span>請勾選產品</span></legend>
				<template x-for="(item, idx) in products" :key="idx">
					<div class="row">
						<label class="checkbox large s3 check-amber">
							<input type="checkbox" :name="`searchProductIds[]`" x-model="searchData.productIds" :value="item.id">
							<span x-text="item.name"></span>
						</label>
					</div>
				</template>
			</fieldset>
		</template>
			
		<div class="space"></div>
		<nav class="right-align group split">
			<button type="submit" class="btn-search left-round large"><i>search</i>查詢</button>
			<button @click="resetSearch()" type="button" class="btn-search-reset right-round square large"><i>backspace</i></button>
		</nav>
	</form>
</dialog>
<!-- Search panel end -->

<div x-data="@js($viewModel->statisticsData())" class="content-wrapper">
	<header class="page-nav">
		<nav>
			<!--button type="button" class="btn-show-search button circle" data-ui="#searchPanel"><i>search</i></button-->
			<button type="button" class="btn-show-search button circle extend" data-ui="#searchPanel">
				<i>search</i>
				<span>查詢</span>
			</button>
			
			<template x-if="exportAction != ''">
				<a :href="`javascript:window.location.href='${exportAction}'`" class="button circle extend red" type="button">
					<i>download_2</i>
					<span>下載</span>
				</a>
				<label class="switch icon">
					<input type="checkbox" x-model="$store.sales.showAmount">
					<span>
						<i>attach_money</i>
					</span>
				</label>
			</template>
		</nav>
	</header>
	
	<template x-if="status && !statistics.brandId">
		<!-- Loading -->
		<section class="container">
			<pre><i>arrow_warm_up</i>點擊查詢按鈕執行查詢</pre>
		</section>
	</template>
	
	<template x-if="!status">
		<section class="container">
			<article class="error-container border">
				<div class="row">
					<i>error</i><div class="max">查詢時發生錯誤，請重新查詢</div>
				</div>
			</article>
		</section>
	</template>
	
	<template x-if="status && statistics.brandId">
		<section class="sales-list container">
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
				<div class="page padding active scroll" id="tab-area">
					<section class="statistics-area">
						<table>
							<thead>
								<tr>
									<th x-text="statistics.area.header.areaName"></th>
									<th x-text="statistics.area.header.shopCount"></th>
									<template x-for="pName in statistics.area.header.products" :key="pName">
										<th x-text="pName"></th>
									</template>
								</tr>
							</thead>
							<tbody>
								<template x-for="(areaData, areaId) in statistics.area.data" :key="areaId">
								<tr>
									<td x-text="areaData.areaName"></td>
									<td x-text="areaData.shopCount"></td>
									<template x-for="(pName, pId) in statistics.area.header.products" :key="pId">
									<td>
										<span x-show="!$store.sales.showAmount" x-text="areaData.products[pId]?.totalQty || 0"></span>
										<span x-show="$store.sales.showAmount" x-text="'$' + Math.round(areaData.products[pId]?.totalAmount || 0)"></span>
									</td>
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
									<template x-for="pName in statistics.shop.header.products" :key="pName">
										<th x-text="pName"></th>
									</template>
								</tr>
							</thead>
							<tbody>
								<template x-for="(shopData, idx) in statistics.shop.data" :key="idx">
								<tr>
									<td x-text="shopData.areaName"></td>
									<td x-text="shopData.shopId"></td>
									<td x-text="shopData.shopName"></td>
									<template x-for="(pName, pId) in statistics.shop.header.products" :key="pId">
										<td>
											<span x-show="!$store.sales.showAmount" x-text="shopData.products[pId]?.totalQty || 0"></span>
											<span x-show="$store.sales.showAmount" x-text="'$' + Math.round(shopData.products[pId]?.totalAmount || 0)"></span>
										</td>
									</template>
								</tr>
								</template>
							</tbody>
						</table>
					</section>
				</div>
			</div>
		</section>
	</template>
</div>

@endsection