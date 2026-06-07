	<!-- 門店 -->
	<div x-data="{shop:@js($viewModel->statisticsData('shop'))}" class="page padding" id="tab-shop">
		<section class="statistics-store scrollbar" :class="response.brandCode">
			<table class="stripes">
				<thead>
					<tr>
						<th x-text="shop.header.areaName"></th>
						<th x-text="shop.header.shopId"></th>
						<th x-text="shop.header.shopName"></th>
						<template x-for="pName in shop.header.products" :key="pName">
							<th x-text="pName"></th>
						</template>
					</tr>
				</thead>
				<tbody>
					<template x-for="(shopData, idx) in shop.data" :key="idx">
					<tr>
						<td x-text="shopData.areaName"></td>
						<td x-text="shopData.shopId"></td>
						<td x-text="shopData.shopName"></td>
						<template x-for="(pName, pId) in shop.header.products" :key="pId">
							<td>
								<span x-show="!$store.sales.showAmount" x-text="shopData.products[pId]?.totalQty || 0"></span>
								<span x-show="$store.sales.showAmount" x-text="'$' + Math.round(shopData.products[pId]?.totalAmount || 0)"></span>
							</td>
						</template>
					</tr>
					</template>
				</tbody>
			</table>
		</section>
	</div>