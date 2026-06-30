<section x-data="{store:@js($viewModel->statisticsData('store')), hasResult:@js($viewModel->statisticsData('hasResult'))}" class="ezorder-pos-list container">
	<article x-show="!hasResult" class="secondary-container border">
		<div class="row">
			<i>info</i><div class="max">查無符合資料</div>
		</div>
	</article>
	
	<div x-show="hasResult" class="statistics">
		<!-- 門店 -->
		<div class="statistics-list padding">
			<section class="statistics-store scrollbar" :class="response.brandCode">
				<table class="stripes border">
					<thead>
						<tr>
							<template x-for="(col, idx) in store.header" :key="idx">
								<th x-text="col"></th>
							</template>
						</tr>
					</thead>
					<tbody>
						<template x-for="(data, storeIdx) in store.data" :key="storeIdx">
						<tr>
							<td x-text="data.storeKey"></td>
							<td x-text="data.storeName"></td>
							<td x-text="data.areaName"></td>
							<td x-text="data.businessDays"></td>
							<td x-text="data.ezOrderCount"></td>
							<td x-text="Helper.formatDollar(data.ezAmount)"></td>
							<td x-text="data.posOrderCount"></td>
							<td x-text="Helper.formatDollar(data.posAmount)"></td>
							<td x-text="Helper.formatDollar(data.avgOrderAmount, 2)"></td>
							<td x-text="Helper.formatDollar(data.avgDayAmount, 2)"></td>
							<td x-text="`${data.countPercent}%`"></td>
							<td x-text="`${data.amountPercent}%`"></td>
						</tr>
						</template>
					</tbody>
				</table>
			</section>
		</div>
	</div>
		
</section>
	