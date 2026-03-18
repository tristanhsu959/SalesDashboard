@extends('layouts.app')
@use('App\Enums\Brand')

@push('styles')
    <link href="{{ asset('styles/product/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/product/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Content -->
	<header class="page-nav">
		<nav>
			<a href="{{ route('product.create') }}" class="btn-create button circle"><i>add</i></a>
		</nav>
	</header>
	
@if($viewModel->status() === TRUE)	
	<form x-data='productList(@json($viewModel->list),@json($viewModel->options["brands"]) )' action="" method="post" x-ref="productListForm">
		@csrf
		<section class="product-list container">
			<div>
				<div class="tabs">
					<template x-for="(brand, key) in brands" :key="key">
						<a :data-ui="'#page-' + key" x-text="brand" :class="activeTab == key ? 'active':''" @click="$store.product.tabIndex = key" class="tab-pink"></a>
					</template>
				</div>
				
				<template x-for="(brand, key) in brands">
				<div class="page padding" :id="'page-' + key" :class="activeTab == key ? 'active':''">
					<ul class="list border">
						<template x-for="item in products[key]">
						<li>
							<span class="brand-label" x-text="item.productBrand == 1 ? '八':'御'" :class="item.productBrand == 1 ? 'bf':'bg'"></span>
							<div class="max">
								<h6 x-text="item.productName"></h6>
							</div>
							<div>
								<a :href="'{{ route('product.update', ['_ID_']) }}'.replace('_ID_', item.productId)" class="btn-edit button circle small">
									<i class="small">edit</i>
								</a>
								<a :href="'{{ route('product.delete', ['_ID_']) }}'.replace('_ID_', item.productId)" @click.prevent="confirmDelete($el.href)" class="btn-delete button circle small">
									<i class="small">delete</i>
								</a>
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