@extends('layouts.app')
@use('App\Enums\Brand')

@push('styles')
    <link href="{{ asset('styles/sales_setting/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/sales_setting/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Content -->
	<header x-data class="page-nav">
		<nav>
			<button type="submit" class="btn-save button circle" form="salesSettingForm"><i>save</i></button>
		</nav>
	</header>
	
@if($viewModel->status() === TRUE)
	<form x-data='salesSetting(@json($viewModel->settings), @json($viewModel->options))' action="{{route('sales_setting.update.post')}}" method="post" id="salesSettingForm">
		@csrf
		<section class="sales-setting container">
			<div>
				<div class="tabs center-align">
					<template x-for="(brand, key) in brands" :key="key">
						<a :data-ui="'#page-' + key" x-text="brand" :class="activeTab == key ? 'active':''" @click="activeTab = key"></a>
					</template>
				</div>
				
				<template x-for="(productList, brand) in products">
				<div class="page padding" :id="'page-' + brand" :class="activeTab == brand ? 'active':''">
					<ul class="list border">
						<template x-for="item in productList">
						<li>
							<span class="brand-label" x-text="item.productBrand == 1 ? '八':'御'" :class="item.productBrand == 1 ? 'bf':'bg'"></span>
							<div class="max">
								<h6 x-text="item.productName"></h6>
							</div>
							<div>
								<label class="switch icon">
									<input type="checkbox" :name="`settings[${brand}][]`" :value="item.productId" :checked="settings[brand]">
									<span>
										<i>close</i>
										<i>done</i>
									</span>
								</label>
							</div>
						</li>
						</template>
					</ul>
				</div>
				</template>
			</div>
		</section>
	</form>

@endif
<!-- Content -->
@endsection