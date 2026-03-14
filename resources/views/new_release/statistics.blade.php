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
		<div class="field label suffix round border field-light-blue" :class="Helper.hasError(errors, 'newItemId')">
			<select x-model="searchData.newItemId" name="searchNewItemId" @change="initSearchStDate($event.target.value);" x-effect="$nextTick(() => $el.value = searchData.newItemId)">
				<option value="0">請選擇</option>
				<template x-for="(item, key) in options.newItems" :key="key">
					<option x-text="item.name" :value="item.id"></option>
				</template>
			</select>
			<label>新品</label>
			<i>arrow_drop_down</i>
		</div>
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'stDate')">
			<input type="date" name="searchStDate" maxlength="10" x-model="searchData.stDate" x-ref="searchStDate" @input="errors.delete('stDate')" :max="searchData.today">
			<label>開始日期</label>
		</div>
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'endDate')">
			<input type="date" name="searchEndDate" maxlength="10" x-model="searchData.endDate" x-ref="searchEndDate" @input="errors.delete('endDate')" :max="searchData.today">
			<label>結束日期</label>
		</div>
		
		<nav class="right-align group split">
			<button type="submit" class="btn-search left-round large"><i>search</i>查詢</button>
			<button @click="resetSearch()" type="button" class="btn-search-reset right-round square large"><i>backspace</i></button>
		</nav>
	</dialog>
</form>
<!-- Search panel end -->

<header class="page-nav" :class="isTop ? 'blue-grey10' : 'orange'">
	<nav>
		<button type="button" class="btn-show-search button circle" data-ui="#searchPanel"><i>search</i></button>
	
		@if ($viewModel->hasExportData())
		<a href="javascript:window.location.href='{{ $viewModel->getFormAction(TRUE) }}'" class="button circle red" type="button">
			<span class="material-symbols-outlined filled-icon">download_2</span>
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
		<div class="statistics">
			<div class="tabs cyan-text">
				<a class="active" data-ui="#tab-area">區域彙總</a>
				<a data-ui="#tab-shop">店別明細</a>
				<a data-ui="#tab-ranking-asc">當日銷售前10名</a>
				<a data-ui="#tab-ranking-desc">當日銷售後10名</a>
			</div>
			
			<!-- 區域彙總 -->
			<div class="page padding active" id="tab-area">
				<section class="statistics-area">
					<div class="grid header">
						<div class="s2">區域</div>
						<div class="s2">店家數</div>
						<div class="s2">銷售總量</div>
						<div class="s2">平均日銷售量</div>
						<div class="s2">每店平均銷量</div>
						<div class="s2">每店平均日銷量</div>
					</div>
					
					@foreach($viewModel->statistics['area'] as $id => $area)
					<div class="grid data">
						<div class="s2">{{ $id == 'total' ? '全區合計' : $viewModel->getAreaName($id) }}</div>
						<div class="s2">{{ data_get($area, 'shopCount', 0) }}</div>
						<div class="s2">{{ data_get($area, 'totalQty', 0) }}</div>
						<div class="s2">{{ data_get($area, 'avgDayQty', 0) }}</div>
						<div class="s2">{{ data_get($area, 'avgShopQty', 0) }}</div>
						<div class="s2">{{ data_get($area, 'avgDayShopQty', 0) }}</div>
					</div>
					@endforeach
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
								@foreach($viewModel->statistics['dayHeader'] as $date)
								<th class="col-date">
									<span>{{ $viewModel->showHeaderYear(Str::before($date, '-')) }}</span>
									<span>{{ Str::after($date, '-') }}</span>
								</th>
								@endforeach
								<th>銷售總量</th>
								<th>平均銷售數量</th>
							</tr>
						</thead>
						<tbody>
							@foreach($viewModel->statistics['shop'] as $shop)
							<tr>
								<th>{{ $viewModel->getAreaName($shop['areaId']) }}</th>
								<th>{{ $shop['shopId'] }}</th>
								<th>{{ $shop['shopName'] }}</th>
								
								@foreach($viewModel->statistics['dayHeader'] as $date)
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
			</div>
			<div class="page padding" id="tab-ranking-asc">
				<section class="statistics-ranking">
					<article class="border ranking-top">
						<ul class="list border">
							<!--只顯示第一家,因數量太多-->
							@foreach($viewModel->statistics['top'] as $ranking => $shopGroup)
							<li>
								<div class="ranking">{{ $ranking + 1 }}</div>
								<div class="info">
									{{ $viewModel->getAreaName($shopGroup[0]['areaId']) }}
									<div class="name">{{ $shopGroup[0]['shopName'] }}</div>
									<span>{{ $shopGroup[0]['shopId'] }}</span>
								</div>
								<span class="badge none primary">{{ $shopGroup[0]['qty'] }}</span>
								<div class="max"></div>
								<label>共 {{ count($shopGroup) }} 店家</label>
							</li>
							@endforeach
						</ul>
					</article>
				</section>
			</div>
			<div class="page padding" id="tab-ranking-desc">
				<section class="statistics-ranking">
					<article class="border ranking-last">
						<ul class="list border">
							<!--只顯示第一家,因數量太多-->
							@foreach($viewModel->statistics['last'] as $ranking => $shopGroup)
							<li>
								<div class="ranking">{{ $ranking + 1 }}</div>
								<div class="info">
									{{ $viewModel->getAreaName($shopGroup[0]['areaId']) }}
									<div class="name">{{ $shopGroup[0]['shopName'] }}</div>
									<span>{{ $shopGroup[0]['shopId'] }}</span>
								</div>
								<span class="badge none secondary">{{ $shopGroup[0]['qty'] }}</span>
								<div class="max"></div>
								<label>共 {{ count($shopGroup) }} 店家</label>
							</li>
							@endforeach
						</ul>
					</article>
				</section>
			</div>
		</div>
		@endif
	</section>
	@endif
@endif

@endsection