	<!-- 門店 -->
	<div x-data="{store:@js($viewModel->statisticsData('store'))}" class="page padding" id="tab-shop">
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
					<template x-for="(storeData, idx) in store.data" :key="idx">
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
		</section>
	</div>