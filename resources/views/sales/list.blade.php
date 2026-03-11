@extends('layouts.app')
@use('App\Enums\Area')
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
	</nav>
	@if ($viewModel->hasExportData())
	<div class="page-action">
		<a href="{{ route('bg.sales.export', ['token' => $viewModel->statistics['exportToken']]) }}" class="btn btn-export" type="button">
			<span class="material-symbols-outlined filled-icon">download_2</span>
		</a>
	</div>
	@endif
</header>
	
@if($viewModel->status() === TRUE)
	@if($viewModel->isDataEmpty())
		<div class="alert alert-danger">
			無符合資料
		</div>
	@else
		<ul class="nav nav-tab" id="nav-tab" role="tablist">
			<li class="nav-item" role="presentation">
				<button class="nav-link active" id="nav-area-tab" type="button" role="tab" 
					data-bs-toggle="pill" data-bs-target="#area" aria-controls="nav-area" aria-selected="true">
					區域彙總
				</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="nav-shop-tab" type="button" role="tab" 
					data-bs-toggle="pill" data-bs-target="#shop" aria-controls="nav-shop" aria-selected="true">
					店別明細
				</button>
			</li>
		</ul>

		<div class="tab-content" id="nav-tabContent">
			<!-- 區域彙總 -->
			<div class="tab-pane fade show active" id="area" role="tabpanel" aria-labelledby="nav-area-tab" tabindex="0">
				<section class="statistics-area section-wrapper">
					<div class="d-table-row">
						<div class="d-table-cell">區域</div>
						@foreach($viewModel->statistics['header'] as $product)
						<div class="d-table-cell">{{ $product['productName'] }}</div>
						@endforeach
					</div>
					
					@foreach($viewModel->statistics['area'] as $name => $area)
					<div class="d-table-row">
						<div class="d-table-cell">{{ $name }}</div>
						@foreach($viewModel->statistics['header'] as $no => $product)
						<div class="d-table-cell">
							{{ Number::currency(intval(data_get($area, "products.{$no}.amount")), precision: 0) }}
							/
							{{ intval(data_get($area, "products.{$no}.quantity")) }}
							{{-- $product['unit'] --}}
						</div>
						@endforeach
					</div>
					@endforeach
					
					<div class="d-table-row">
						<div class="d-table-cell">合計</div>
						@foreach($viewModel->statistics['header'] as $product)
						<div class="d-table-cell">{{ Number::currency(intval($product['totalAmount']), precision: 0) }} / {{ intval($product['totalQty']) }}</div>
						@endforeach
					</div>
				</section>
			</div>
			
			<!-- 店別明細 -->
			<div class="tab-pane fade" id="shop" role="tabpanel" aria-labelledby="nav-shop-tab" tabindex="0">
				
				<section class="statistics-shop section-wrapper scrollbar">
					<div class="d-table-row">
						<div class="d-table-cell">區域</div>
						<div class="d-table-cell">門店代號</div>
						<div class="d-table-cell">門店名稱</div>
						@foreach($viewModel->statistics['header'] as $product)
						<div class="d-table-cell">{{ $product['productName'] }}</div>
						@endforeach
						<!--div class="d-table-cell">總金額</div-->
					</div>
					
					@foreach($viewModel->statistics['shop'] as $shop)
					<div class="d-table-row">
						<div class="d-table-cell">{{ $shop['area'] }}</div>
						<div class="d-table-cell">{{ $shop['shopId'] }}</div>
						<div class="d-table-cell">{{ $shop['shopName'] }}</div>
						@foreach($viewModel->statistics['header'] as $no => $product)
						<div class="d-table-cell">
							{{ Number::currency(intval(data_get($shop, "products.{$no}.amount")), precision: 0) }}
							/
							{{ intval(data_get($shop, "products.{$no}.quantity")) }}
						</div>
						@endforeach
					</div>
					@endforeach
				</section>
				
			</div>
			
		</div>
	@endif <!-- Empty -->
@endif <!-- Status -->
@endsection