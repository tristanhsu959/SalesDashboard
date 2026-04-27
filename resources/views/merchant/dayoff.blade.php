 
	<section x-data='storeDayoff(@json($viewModel->statistics))' class="store-list container">
		@if($viewModel->isDataEmpty())
		<article class="error-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
		@else
		<div class="area-content">
			<div class="tabs cyan-text">
				<a class="active" data-ui="#tab-area">店休-區域</a>
				<a data-ui="#tab-shop">店休-店別明細</a>
			</div>
			
			<!-- 區域 -->
			<div class="page padding active" id="tab-area">
				<section class="statistics-area scrollbar {{$viewModel->getBrandCode()}}">
					<table class="stripes">
						<thead>
							<tr>
								<template x-for="area in statistics.areaDayoff.header" :key="area">
									<th x-text="area"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(store, idx) in statistics.areaDayoff.store" :key="idx">
							<tr>
								<td x-text="store.areaName"></td>
								<td x-text="store.total"></td>
								<td x-text="store.dayoffCount"></td>
								<td x-text="store.percent"></td>
							</tr>
							</template>
						</tbody>
					</table>
				</section>
			</div>
			
			<!-- 門店 -->
			<div class="page padding" id="tab-shop">
				<section class="statistics-store scrollbar {{$viewModel->getBrandCode()}}">
					<table class="stripes">
						<thead>
							<tr>
								<template x-for="header in statistics.dayoff.header" :key="header">
									<th x-text="header"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(store, idx) in statistics.dayoff.store" :key="idx">
							<tr>
								<td x-text="store.posId"></td>
								<td x-text="store.areaName"></td>
								<td x-text="store.storeNo"></td>
								<td x-text="store.storeName"></td>
							</tr>
							</template>
						</tbody>
					</table>
				</section>
			</div>
		</div>
		@endif
	</section>
