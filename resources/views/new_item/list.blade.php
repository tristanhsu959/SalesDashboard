@extends('layouts.app')
@use('App\Enums\Brand')

@push('styles')
    <!--link href="{{ asset('styles/product/list.css') }}" rel="stylesheet"-->
@endpush

@push('scripts')
    <script src="{{ asset('scripts/new_item/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Content -->
@if($viewModel->status() === TRUE)

	<form x-data='newItemList(@json($viewModel->list), @json($viewModel->options))' action="" method="post" x-ref="newItemListForm">
		@csrf
		<section class="product-list container">
			@if(empty(($viewModel->list['products'])))
			<article class="error-container border">
				<div class="row">
					<i>info</i><div class="max">無新品設定資料</div>
				</div>
			</article>
			@else
			<div>
				<div class="tabs center-align">
					<template x-for="(brand, value) in options.brands">
						<a :data-ui="'#page-' + value" x-text="brand" :class="activeTab == value ? 'active':''"></a>
					</template>
				</div>
				
				<template x-for="(brand, value) in options.brands">
				<div class="page padding" :id="'page-' + value" :class="activeTab == value ? 'active':''">
					<ul class="list border">
						<template x-for="item in products[value]">
						<li>
							<button class="circle">A</button>
							<div class="max">
								<h6 class="small" x-text="item.productName">Headline</h6>
							</div>
							<div>
  <button>
    <i>today</i>
    <span>Date</span>
  </button>
  <input type="date">
</div>
						</li>
						</template>
					</ul>
				</div>
				</template>
			@endif
		</section>
	</form>

@endif
<!-- Content -->
@endsection