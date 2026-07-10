	<!-- 門店 -->
	<div x-data="statisticsStore(@js($viewModel->statisticsData('store')))" class="page padding" id="tab-shop">
		<section class="statistics-store scrollbar" :class="response.brandCode">
			<table class="stripes">
				<thead>
					<tr>
						<th x-text="store.header.areaName"></th>
						<th x-text="store.header.shopId"></th>
						<th x-text="store.header.storeKey"></th>
						<th x-text="store.header.shopName"></th>
						<template x-for="pName in store.header.products" :key="pName">
							<th x-text="pName"></th>
						</template>
					</tr>
				</thead>
				<tbody>
					<template x-for="(storeData, idx) in filterStore" :key="idx">
					<tr>
						<td x-text="storeData.areaName"></td>
						<td x-text="storeData.shopId"></td>
						<td x-text="storeData.storeKey"></td>
						<td><button class="transparent circle small purple-text"><i>more_vert</i></button><span x-text="storeData.shopName"></span></td>
						
						<template x-for="(pName, pId) in store.header.products" :key="pId">
							<td>
								<span x-show="!$store.sales.showAmount" x-text="storeData.products[pId]?.totalQty || 0"></span>
								<span x-show="$store.sales.showAmount" x-text="Helper.formatDollar(Math.round(storeData.products[pId]?.totalAmount || 0))"></span>
							</td>
						</template>
					</tr>
					</template>
				</tbody>
			</table>
		</section>
	</div>
	
	<dialog x-data="storeDetail(@js($viewModel->statisticsData('detail')))" class="store-detail left">
		<div class="row">
			<div class="left-align max">
				<h5 class="">Bottom</h5>
				<div>Some text here</div>
			</div>
			<nav class="right-align">
				<button class="transparent circle"><i>close</i></button >
			</nav>
		</div>
		
		<table class="stripes">
			<thead>
				<tr>
					<th x-text="detail.header.productName"></th>
					<template x-for="pName in store.header.products" :key="pName">
						<th x-text="pName"></th>
					</template>
				</tr>
			</thead>
			<tbody>
				<template x-for="(storeData, idx) in filterStore" :key="idx">
				<tr>
					<td x-text="storeData.areaName"></td>
					<td x-text="storeData.shopId"></td>
					<td x-text="storeData.storeKey"></td>
					<td x-text="storeData.shopName"></td>
					<template x-for="(pName, pId) in store.header.products" :key="pId">
						<td>
							<span x-show="!$store.sales.showAmount" x-text="storeData.products[pId]?.totalQty || 0"></span>
							<span x-show="$store.sales.showAmount" x-text="Helper.formatDollar(Math.round(storeData.products[pId]?.totalAmount || 0))"></span>
						</td>
					</template>
				</tr>
				</template>
			</tbody>
		</table>
		
		
	</dialog>

