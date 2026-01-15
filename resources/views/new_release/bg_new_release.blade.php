@extends('layouts.master')
@use('App\Enums\Area')

@push('styles')
    <link href="{{ asset('styles/new_release/new_release.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/new_release/new_release.js') }}" defer></script>
@endpush

@section('navHead')
<div class="new-release-info">
	<div>發售日</div>
	<div>{{ $viewModel->getSaleDate() }}</div>
</div>
@endsection


@section('content')
<!-- Search by Date -->
<form action="{{ route('bg.new_releases.search', ['segment' => $viewModel->getSegment() ]) }}" method="post" id="searchForm">
@csrf
<input type="hidden" name="configKey" value="{{ $viewModel->configKey }}">
<input type="hidden" name="functionKey" value="{{ $viewModel->getFunctionKey() }}">

<section class="searchbar section-wrapper dp-2">
	<div class="input-field field-light-blue field">
		<input type="date" class="form-control valid" 
			id="searchStDate" name="searchStDate" value="{{ $viewModel->getSearchStDate() }}" 
			maxlength="10" placeholder=" " min="{{ $viewModel->getSaleDate() }}">
		<label for="searchStDate" class="form-label">開始日期</label>
	</div>
	<div class="input-field field-light-blue field">
		<input type="date" class="form-control valid" 
			id="searchEndDate" name="searchEndDate" value="{{ $viewModel->getSearchEndDate() }}" 
			maxlength="10" placeholder=" " max="{{ $viewModel->getSaleEndDate() }}">
		<label for="searchEndDate" class="form-label">結束日期</label>
	</div>
	
	<button class="btn btn-search" type="button">
		<span class="material-symbols-outlined filled-icon">search</span>
	</button>
	<button class="btn btn-search-reset" type="button">
		<span class="material-symbols-outlined filled-icon">backspace</span>
	</button>
	<div class="sales-info">銷售日：{{ $viewModel->getSaleDate() }}</div>
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
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="nav-ranking-tab" type="button" role="tab" 
				data-bs-toggle="pill" data-bs-target="#ranking" aria-controls="nav-ranking" aria-selected="true">
				銷售排名
			</button>
		</li>
	</ul>
	
	<div class="tab-content" id="nav-tabContent">
		<!-- 區域彙總 -->
		<div class="tab-pane fade show active" id="area" role="tabpanel" aria-labelledby="nav-area-tab" tabindex="0">
			@if(empty($viewModel->statistics['area']))
			<section class="statistics-area section-wrapper empty">
				無符合資料
			</section>
			@else
			<section class="statistics-area section-wrapper">
				<div class="row">
					<div class="col">區域</div>
					<div class="col">店家數</div>
					<div class="col">區域銷售總量</div>
					<div class="col">區域平均日銷售量</div>
					<div class="col">區域每店平均銷量</div>
					<div class="col">區域每店平均日銷量</div>
				</div>
				
				@foreach($viewModel->statistics['area'] as $name => $area)
				<div class="row">
					<div class="col">{{ $name == 'total' ? '全區合計' : $name }}</div>
					<div class="col">{{ data_get($area, 'shopCount', 0) }}</div>
					<div class="col">{{ data_get($area, 'totalQty', 0) }}</div>
					<div class="col">{{ data_get($area, 'avgDayQty', 0) }}</div>
					<div class="col">{{ data_get($area, 'avgShopQty', 0) }}</div>
					<div class="col">{{ data_get($area, 'avgDayShopQty', 0) }}</div>
				</div>
				@endforeach
			</section>
			@endif
		</div>
		
		<!-- 店別明細 -->
		<div class="tab-pane fade" id="shop" role="tabpanel" aria-labelledby="nav-shop-tab" tabindex="0">
			@if(empty($viewModel->statistics['shop']))
			<section class="statistics-shop section-wrapper empty">
				無符合資料或無瀏覽權限
			</section>
			@else
			<section class="statistics-shop section-wrapper scrollbar">
				<table class="table table-striped">
					<thead>
						<tr>
							<th>區域</th>
							<th>門店代號</th>
							<th>門店名稱</th>
							@foreach($viewModel->getDateRange() as $date)
							<th class="col-date">
								<span>{{ Str::before($date, '-') }}</span>
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
							<th>{{ Area::getLabelByValue($shop['area']) }}</th>
							<th>{{ $shop['shopId'] }}</th>
							<th>{{ $shop['shopName'] }}</th>
							
							@foreach($viewModel->getDateRange() as $date)
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
			@endif
		</div>
		
		<!-- 銷售排名 -->
		<div class="tab-pane fade" id="ranking" role="tabpanel" aria-labelledby="nav-ranking-tab" tabindex="0">
			@if(empty($viewModel->statistics['top']) && empty($viewModel->statistics['last']))
			<section class="statistics-ranking section-wrapper empty">
				無符合資料或無瀏覽權限
			</section>
			@else
			<section class="statistics-ranking section-wrapper container-fluid">
				<div class="card ranking-top">
					<div class="card-body">
						<h5 class="card-title">當日銷售前10名</h5>
						<ul class="list-group">
						@foreach($viewModel->statistics['top'] as $ranking => $shopGroup)
							@foreach($shopGroup as $shop)
							<li class="list-group-item">
								<div class="ranking">{{ $ranking + 1 }}</div>
								<div class="info">
									{{ Area::getLabelByValue($shop['area']) }}
									<div class="name">{{ $shop['shopName'] }}</div>
									<span>{{ $shop['shopId'] }}</span>
								</div>
								<span class="badge rounded-pill">{{ $shop['todayQty'] }}</span>
							</li>
							@endforeach
						@endforeach
						</ul>
					</div>
				</div>
				<div class="card ranking-last">
					<div class="card-body">
						<h5 class="card-title">當日銷售後10名</h5>
						<ul class="list-group">
						@foreach($viewModel->statistics['last'] as $ranking => $shopGroup)
							@foreach($shopGroup as $shop)
							<li class="list-group-item">
								<div class="ranking">{{ $ranking + 1 }}</div>
								<div class="info">
									{{ Area::getLabelByValue($shop['area']) }}
									<div class="name">{{ $shop['shopName'] }}</div>
									<span>{{ $shop['shopId'] }}</span>
								</div>
								<span class="badge rounded-pill">{{ $shop['todayQty'] }}</span>
							</li>
							@endforeach
						@endforeach	
						</ul>
					</div>
				</div>
			</section>
			@endif
		</div>
	</div>
	@endif
@endif
@endsection