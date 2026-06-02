@extends('layouts.app')

@push('styles')
    <link href="{{ asset('styles/purchase_sales/detail.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/purchase_sales/detail.js') }}" defer></script>
@endpush

@section('content')
<header class="page-nav">
	<nav>
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
	<section class="purchase-sale-list container">
		@if($viewModel->isDataEmpty())
		<article class="error-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
		@else
		<div x-data="statistics(@js($viewModel->statistics))" class="statistics">
			<div class="tabs cyan-text">
				<a class="active" data-ui="#tab-purchase">訂貨</a>
				<a data-ui="#tab-sale">銷售</a>
			</div>
			
			<!-- 訂貨資訊 -->
			<div class="page padding active" id="tab-purchase">
				<section class="statistics-list purchase" :class="statistics.brandCode">
					<table class="stripes">
						<thead>
							<tr>
								<template x-for="col in statistics.purchaseData.header" :key="col">
									<th class="s2" x-text="col"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(data, idx) in statistics.purchaseData.data" :key="idx">
							<tr>
								<td x-text="data['shortCode']"></td>
								<td x-text="data['productName']"></td>
								<td x-text="data['qty']"></td>
								<td x-text="data['amount']"></td>
								<td x-text="data['memo']"></td>
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
		<pre>查無符合資料</pre>
	</section>
	@endif
@endif

@endsection