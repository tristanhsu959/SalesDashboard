@extends('layouts.app')

@push('styles')
    <link href="{{ asset('styles/monthly_filling/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/monthly_filling/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Search panel -->
<form x-data='searchReport(@json($viewModel->search), @json($viewModel->options))' action="{{ $viewModel->getFormAction() }}" method="post" id="searchForm" class="no-margin" novalidate @submit.prevent="search()">
	@csrf

	<dialog id="searchPanel" class="right">
		<h5>查詢</h5>
		
		<div class="mode-group">
			<div class="field middle-align">
				<nav class="wrap">
					<template x-for="(name, id) in options.mode.type" :key="id">
						<label class="radio field-red">
							<input type="radio" name="searchType" x-model="searchData.type" :value="id">
							<span x-text="name"></span>
						</label>
					</template>
				</nav>
			</div>
			<div class="field middle-align">
				<nav class="wrap">
					<template x-for="(name, id) in options.mode.range" :key="id">
						<label class="radio field-light-blue">
							<input type="radio" name="searchRange" x-model="searchData.range" :value="id" @change="changeDateInput()">
							<span x-text="name"></span>
						</label>
					</template>
				</nav>
			</div>
		</div>
		
		<div class="space"></div>
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'stDate')">
			<input type="month" name="searchStDate" maxlength="7" x-model="searchData.stDate" x-ref="searchStDate" @input="errors.delete('stDate')" :max="searchData.currentDate">
			<label>開始日期</label>
		</div>
		
		<div class="space"></div>
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'endDate')">
			<input type="month" name="searchEndDate" maxlength="7" x-model="searchData.endDate" x-ref="searchEndDate" @input="errors.delete('endDate')" :max="searchData.currentDate">
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
		@include($viewModel->getPartialView())
	@else
	<section class="container">
		<pre><i>arrow_warm_up</i>點擊查詢按鈕執行查詢</pre>
	</section>
	@endif
@endif

@endsection