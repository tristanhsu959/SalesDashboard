@extends('layouts.app')

@push('styles')
    <link href="{{ asset('styles/shipments/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/shipments/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Search panel -->
<form x-data='searchShipments(@json($viewModel->search), @json($viewModel->options))' action="{{ $viewModel->getFormAction() }}" method="post" id="searchForm" class="no-margin" novalidate @submit.prevent="search()">
	@csrf

	<dialog id="searchPanel" class="right">
		<h5>查詢</h5>
		
		<div class="search-condition">
			<div class="condition-group">
				<div class="space"></div>
				<nav class="wrap">
					<template x-for="(name, id) in options.mode.type" :key="id">
						<label class="radio field-red">
							<input type="radio" name="searchType" x-model="searchData.type" :value="id">
							<span x-text="name"></span>
						</label>
					</template>
					
					<template x-for="(name, id) in options.mode.calc" :key="id">
						<label class="radio field-light-blue">
							<input type="radio" name="searchCalc" x-model="searchData.calc" :value="id">
							<span x-text="name"></span>
						</label>
					</template>
				</nav>
				
				<div class="space"></div>
				<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'stDate')">
					<input type="date" name="searchStDate" maxlength="10" x-model="searchData.stDate" x-ref="searchStDate" @input="errors.delete('stDate')" :max="searchData.today">
					<label>開始日期</label>
				</div>
				
				<div class="space"></div>
				<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'endDate')">
					<input type="date" name="searchEndDate" maxlength="10" x-model="searchData.endDate" x-ref="searchEndDate" @input="errors.delete('endDate')" :max="searchData.today">
					<label>結束日期</label>
					<output class="red-text">查詢日期為到貨日期</output>
				</div>
				
				<nav class="wrap">
					<template x-for="(name, id) in options.mode.by" :key="id">
						<label class="radio field-purple">
							<input type="radio" name="searchBy" x-model="searchData.by" :value="id">
							<span x-text="name"></span>
						</label>
					</template>
				</nav>
			</div>
			
			<div x-show="searchData.by == 'keyword'" class="field label border round field-light-blue" :class="Helper.hasError(errors, 'keyword')">
				<input type="text" name="searchKeyword" maxlength="30" x-model="searchData.keyword" x-ref="searchKeyword" @input="errors.delete('keyword')">
				<label>產品名稱</label>
			</div>
			
			<div x-show="searchData.by == 'category'" class="field label suffix round border field-light-blue" :class="Helper.hasError(errors, 'category')">
				<select x-model="searchData.category" name="searchCategory" @change="searchData.shortCodes = []">
					<option value="">請選擇</option>
					<template x-for="(name, catId) in options.category" :key="catId">
						<option x-text="name" :value="catId" :selected="searchData.category == catId"></option>
					</template>
				</select>
				<label>類別</label>
				<i>arrow_drop_down</i>
			</div>
			
			<template x-for="(products, catId) in options.products" :key="catId">
				<fieldset x-show="searchData.category == catId && searchData.by == 'category'" class="field-dark-blue fieldset">
					<legend><i class="small red-text">asterisk</i><span>請勾選產品</span></legend>
					<template x-for="(item, idx) in products" :key="idx">
						<div class="row">
							<label class="checkbox large s3 check-amber">
								<input type="checkbox" :name="`searchShortCodes[]`" x-model="searchData.shortCodes" :value="item.shortCode">
								<span x-text="item.productName"></span>
							</label>
						</div>
					</template>
				</fieldset>
			</template>
				
			<div>
				<div class="space"></div>
				<nav class="right-align split group">
					<button type="submit" class="btn-search left-round large"><i>search</i>查詢</button>
					<button @click="resetSearch()" type="button" class="btn-search-reset right-round square large"><i>backspace</i></button>
				</nav>
			</div>
		</div>
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
		
			@if ($viewModel->search['type'] == 'store')
			<div class="field label border prefix field-filter-dark">
				<i>filter_alt</i>
				<input type="text" x-model="$store.shipmentStore.filter">
				<label>篩選</label>
			</div>
			@endif
		@endif
		
	</nav>
</header>
	
@if($viewModel->status() === TRUE)	
	@if(isset($viewModel->statistics['brandId'])) <!-- loading or not -->
		@include($viewModel->getPartialView())
	@else
	<section class="container">
		<pre><i>arrow_warm_up</i>點擊查詢按鈕執行查詢</pre>
		@if($viewModel->isBafang())
		<pre class="red-border">菜肉餡：Qty X 2.5<br/>新蔬食餡：Qty X 1.8</pre>
		@endif
	</section>
	@endif
@endif

@endsection