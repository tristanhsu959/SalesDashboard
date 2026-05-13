@extends('layouts.app')

@push('styles')
    <link href="{{ asset('styles/new_release/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/new_release/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Search panel -->
<form x-data='searchProduct(@json($viewModel->search), @json($viewModel->options))' action="{{ $viewModel->getFormAction() }}" method="post" id="searchForm" class="no-margin" novalidate @submit.prevent="search()">
	@csrf

	<dialog id="searchPanel" class="right">
		<h5>查詢</h5>
		<div class="space"></div>
		<div class="field label suffix round border field-light-blue" :class="Helper.hasError(errors, 'releaseId')">
			<select x-model="searchData.releaseId" name="searchReleaseId" @change="initSearchStDate($event.target.value);" x-effect="$nextTick(() => $el.value = searchData.releaseId)">
				<option value="0">請選擇</option>
				<template x-for="(item, key) in options.newReleaseProducts" :key="key">
					<option x-text="item.name" :value="item.id"></option>
				</template>
			</select>
			<label>新品</label>
			<i>arrow_drop_down</i>
		</div>
		
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
		<!--button type="button" class="btn-show-search button circle" data-ui="#searchPanel"><i>search</i></button-->
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
		<div x-data="@js($viewModel->statistics)" class="statistics">
			<div class="tabs cyan-text">
				<a class="active" data-ui="#tab-area">區域彙總</a>
				<a data-ui="#tab-shop">店別明細</a>
				<a data-ui="#tab-ranking-asc">當日銷售前10名</a>
				<a data-ui="#tab-ranking-desc">當日銷售後10名</a>
			</div>
			
			<!-- 區域彙總 -->
			<div class="page padding active" id="tab-area">
				<section class="statistics-area grid-table">
					<div class="grid header">
						<template x-for="col in area.header" :key="col">
							<div class="s2" x-text="col"></div>
						</template>
					</div>
					
					<template x-for="(areaData, idx) in area.data" :key="idx">
					<div class="grid data">
						<template x-for="(col, colKey) in area.header" :key="colKey">
							<div class="s2" x-text="areaData[colKey]"></div>
						</template>
					</div>
					</template>
				</section>
			</div>
			
			<!-- 門店 -->
			<div class="page padding" id="tab-shop">
				<section class="statistics-shop scrollbar" :class="brandCode">
					<table class="stripes">
						<thead>
							<tr>
								<template x-for="col in shop.header" :key="col">
									<th x-text="col"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(shopData, idx) in shop.data" :key="idx">
							<tr>
								<template x-for="(col, colKey) in shop.header" :key="colKey">
									<td x-text="shopData[colKey] || 0"></td>
								</template>
							</tr>
							</template>
						</tbody>
					</table>
				</section>
			</div>
			
			<!-- 排名 -->
			<div class="page padding" id="tab-ranking-asc">
				<section class="statistics-ranking">
					<article class="border ranking-top">
						<ul class="list border">
							<!--只顯示第一家,因數量太多-->
							<template x-for="(items, topRanking) in top" :key="topRanking">
							<li>
								<div class="ranking" x-text="topRanking + 1"></div>
								<div class="info">
									<span x-text="items[0]['areaName']"></span>
									<div class="name" x-text="items[0]['shopName']"></div>
									<span x-text="items[0]['shopId']"></span>
								</div>
								<span class="badge none primary" x-text="items[0]['qty']"></span>
								<div class="max"></div>
								<label x-text="`共  ${items.length} 店家`"></label>
							</li>
							</template>
						</ul>
					</article>
				</section>
			</div>
			<div class="page padding" id="tab-ranking-desc">
				<section class="statistics-ranking">
					<article class="border ranking-last">
						<ul class="list border">
							<!--只顯示第一家,因數量太多-->
							<template x-for="(items, lastRanking) in last" :key="lastRanking">
							<li>
								<div class="ranking" x-text="lastRanking + 1"></div>
								<div class="info">
									<span x-text="items[0]['areaName']"></span>
									<div class="name" x-text="items[0]['shopName']"></div>
									<span x-text="items[0]['shopId']"></span>
								</div>
								<span class="badge none secondary" x-text="items[0]['qty']"></span>
								<div class="max"></div>
								<label x-text="`共  ${items.length} 店家`"></label>
							</li>
							</template>
						</ul>
					</article>
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