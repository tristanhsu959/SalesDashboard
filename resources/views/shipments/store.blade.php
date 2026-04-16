 
	<section x-data='statisticsStore(@json($viewModel->statistics))' class="store-list container">
		@if($viewModel->isDataEmpty())
		<article class="error-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
		@else
		<div class="store-content">
			<div class="tabs cyan-text">
				<template x-for="(name, id) in statistics.header.productList" :key="id">
					<a x-text="name" @click="activeProduct = id" :class="{ 'active': activeProduct === id }"></a>
				</template>
			</div>
			
			<!-- 門店 -->
			<template x-for="(name, productId) in statistics.header.productList" :key="productId">
			<div class="page paddin" :class="{ 'active': activeProduct === productId }">
				<section class="statistics-store scrollbar {{$viewModel->getBrandCode()}}">
					<table class="stripes">
						<thead>
							<tr>
								<th>POS ID</th>
								<th>區域</th>
								<!--th>門店代號</th-->
								<th>門店名稱</th>
								<template x-for="(name, id) in statistics.header.dateList" :key="id">
									<th x-text="name"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(store, storeId) in statistics.header['storeList']" :key="storeId">
							<tr>
								<th x-text="store.postId"></th>
								<th x-text="store.areaName"></th>
								<!--th x-text="store.storeNo"></th-->
								<th x-text="store.storeName"></th>
								<template x-for="(date, idx) in statistics.header.dateList" :key="idx">
									<td x-text="statistics.data[productId]?.[storeId]?.[date]?.['qty'] ?? 0"></td>
								</template>
							</tr>
							</template>
								
						</tbody>
					</table>
				</section>
			</div>
			</template>
		</div>
		@endif
	</section>
