@extends('layouts.app')

@push('styles')
    <link href="{{ asset('styles/sales/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/sales/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Search panel -->
<dialog x-data="searchSales(@js($viewModel->searchFormData()))" id="searchPanel" class="right">
	<form :action="searchData.formAction" method="post" id="searchForm" novalidate @submit.prevent="search()" class="vertical">
	@csrf
		<h5>查詢</h5>
		
		<!--nav class="wrap">
			<template x-for="(name, id) in options.mode.type" :key="id">
				<label class="radio field-red">
					<input type="radio" name="searchType" x-model="searchData.type" :value="id" @change="switchConditions()">
					<span x-text="name"></span>
				</label>
			</template>
		</nav-->
		
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'stDate')">
			<input type="date" name="searchStDate" maxlength="10" x-model="searchData.stDate" x-ref="searchStDate" @input="errors.delete('stDate')" :max="searchData.today">
			<label>開始日期</label>
		</div>
		
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'endDate')">
			<input type="date" name="searchEndDate" maxlength="10" x-model="searchData.endDate" x-ref="searchEndDate" @input="errors.delete('endDate')" :max="searchData.today">
			<label>結束日期</label>
		</div>
		
		<div class="field label suffix round border field-light-blue" :class="Helper.hasError(errors, 'category')">
			<select x-model="searchData.category" name="searchCategory"><!-- @change="searchData.productIds = []"-->
				<option value="">請選擇</option>
				<template x-for="(name, catId) in options.category" :key="catId">
					<option x-text="name" :value="catId" :selected="searchData.category == catId"></option>
				</template>
			</select>
			<label>類別</label>
			<i>arrow_drop_down</i>
			<output x-text="`已選 ${searchData.productIds.length} 個項目`" class="red-text"></output>
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

<div x-data="{response:@js($viewModel->responseData())}" class="content-wrapper">
	<header class="page-nav">
		<nav>
			<!--button type="button" class="btn-show-search button circle" data-ui="#searchPanel"><i>search</i></button-->
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
			<template x-if="response.hasResult">
				<label class="switch icon">
					<input type="checkbox" x-model="$store.sales.showAmount">
					<span>
						<i>attach_money</i>
					</span>
				</label>
			</template>
		</nav>
	</header>
	
	<template x-if="response.status && response.isInit">
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
	
	<template x-if="response.status && !response.isInit">
		<section class="sales-list container">
			<article x-show="!response.hasResult" class="secondary-container border">
				<div class="row">
					<i>info</i><div class="max">查無符合資料</div>
				</div>
			</article>
			
			<div x-show="response.hasResult" class="statistics">
				<div class="tabs cyan-text">
					<a class="active" data-ui="#tab-area">區域彙總</a>
					<a data-ui="#tab-shop">店別明細</a>
				</div>
			
				@include('sales.area')
				@include('sales.store')
			</div>
		</section>
	</template>
</div>

@endsection