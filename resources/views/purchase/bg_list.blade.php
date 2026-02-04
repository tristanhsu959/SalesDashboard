@extends('layouts.master')
@use('App\Enums\Area')
@use('Illuminate\Support\Number')

@push('styles')
    <link href="{{ asset('styles/purchase/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/purchase/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Search by Date -->
<form action="{{ route('bg.purchase.search') }}" method="post" id="searchForm">
@csrf

<section class="searchbar section-wrapper dp-2">
	<div class="input-field field-cyan field">
		<input type="date" class="form-control valid" 
			id="searchStDate" name="searchStDate" value="{{ $viewModel->getSearchStDate() }}" 
			maxlength="10" placeholder=" " max="{{ now()->format('Y-m-d') }}">
		<label for="searchStDate" class="form-label">開始日期</label>
	</div>
	<div class="input-field field-cyan field">
		<input type="date" class="form-control valid" 
			id="searchEndDate" name="searchEndDate" value="{{ $viewModel->getSearchEndDate() }}" 
			maxlength="10" placeholder=" " max="{{ now()->format('Y-m-d') }}">
		<label for="searchEndDate" class="form-label">結束日期</label>
	</div>
	
	<button class="btn btn-search" type="button">
		<span class="material-symbols-outlined filled-icon">search</span>
	</button>
	<button class="btn btn-search-reset" type="button">
		<span class="material-symbols-outlined filled-icon">backspace</span>
	</button>
	@if ($viewModel->hasExportData())
	<div class="page-action">
		<a href="{{ route('bg.purchase.export', ['token' => $viewModel->statistics['exportToken']]) }}" class="btn btn-export" type="button">
			<span class="material-symbols-outlined filled-icon">download_2</span>
		</a>
	</div>
	@endif
</section>
</form>
	
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
							{{-- Number::currency(intval(data_get($area, "products.{$no}.amount")), precision: 0) --}}
							
							{{ intval(data_get($area, "products.{$no}.quantity")) }}
							{{-- $product['unit'] --}}
						</div>
						@endforeach
					</div>
					@endforeach
					
					<div class="d-table-row">
						<div class="d-table-cell">合計</div>
						@foreach($viewModel->statistics['header'] as $product)
						<div class="d-table-cell">{{ intval($product['totalQty']) }}</div>
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
							{{ intval(data_get($shop, "products.{$no}.quantity")) }}
							{{-- Number::currency(intval(data_get($shop, "products.{$no}.amount")), precision: 0) --}}
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