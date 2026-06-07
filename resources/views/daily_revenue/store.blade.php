	<!-- 門店 -->
	<div x-data="{shop:@js($viewModel->statisticsData('shop'))}" class="page padding" id="tab-shop">
		<section class="statistics-store scrollbar" :class="response.brandCode">
			<table class="stripes">
				<thead>
					<tr>
						<th x-text="shop.header.areaName"></th>
						<th x-text="shop.header.shopId"></th>
						<th x-text="shop.header.shopName"></th>
						<th x-text="shop.header.shopTypeName"></th>
						<template x-for="(date, dateKey) in shop.header.dayAmount" :key="dateKey">
							<th x-text="date"></th>
						</template>
					</tr>
				</thead>
				<tbody>
					<template x-for="(shopData, shopId) in shop.data" :key="shopId">
					<tr>
						<td x-text="shopData.areaName"></td>
						<td x-text="shopData.shopId"></td>
						<td x-text="shopData.shopName"></td>
						<td x-text="shopData.shopTypeName"></td>
						<template x-for="(date, dateKey) in shop.header.dayAmount" :key="dateKey">
							<td x-text="'$'+ (shopData.dayAmount[date] || 0)"></td>
						</template>
					</tr>
					</template>
				</tbody>
			</table>
		</section>
	</div>