@extends('layouts.app')
@use('App\Enums\Brand')

@push('styles')
    <link href="{{ asset('styles/new_item/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/new_item/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Content -->
	<header class="page-nav">
		<nav>
			<a href="{{ route('new_item.create') }}" class="btn-create button circle"><i>add</i></a>
		</nav>
	</header>
	
@if($viewModel->status() === TRUE)
	<form x-data='newItemList(@json($viewModel->list),@json($viewModel->options["brands"]) )' action="" method="post" x-ref="newItemListForm">
		@csrf
		<section class="new-item-list container">
			<div>
				<div class="tabs">
					<template x-for="(brand, key) in brands" :key="key">
						<a :data-ui="'#page-' + key" x-text="brand" :class="activeTab == key ? 'active':''" @click="$store.newItem.tabIndex = key"></a>
					</template>
				</div>
				
				<template x-for="(brand, key) in brands">
				<div class="page padding" :id="'page-' + key" :class="activeTab == key ? 'active':''">
					<ul class="list border">
						<template x-for="item in newItems[key]">
						<li>
							<i class="fill extra" x-text="item.newItemStatus ? 'check_circle':'cancel'" :class="item.newItemStatus ? 'green-text':'red-text'"></i>
							<div class="max">
								<h6 x-text="item.newItemName"></h6>
								<div class="small sale-date" x-text="'發售日 ' + item.newItemSaleDate"></div>
							</div>
							<div>
								<a :href="'{{ route('new_item.update', ['_ID_']) }}'.replace('_ID_', item.newItemId)" class="btn-edit button circle small">
									<i class="small">edit</i>
								</a>
								<a :href="'{{ route('new_item.delete', ['_ID_']) }}'.replace('_ID_', item.newItemId)" @click.prevent="confirmDelete($el.href)" class="btn-delete button circle small">
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