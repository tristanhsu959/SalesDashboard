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
			<select x-model="searchData.newItemId" name="searchNewItemId" @change="initSearchStDate($event.target.value);errors.delete('newItemId')">
				<option value="">請選擇</option>
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
	</nav>
</header>
	
@if($viewModel->status() === TRUE)	
	@if($viewModel->search['newItemId'])
	<section class="new-release-list container">
		@if($viewModel->isDataEmpty())
		<article class="error-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
		@else
			<i></i>
		@endif
	</section>
	@endif
@endif

@endsection