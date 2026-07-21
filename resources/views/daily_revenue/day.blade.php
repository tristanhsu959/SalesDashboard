<section class="day-revenue-list container">
	<article x-show="!response.hasResult" class="secondary-container border">
		<div class="row">
			<i>info</i><div class="max">查無符合資料</div>
		</div>
	</article>
	
	<div x-show="response.hasResult" class="statistics-list">
		
		<!-- 門店 -->
		<div x-data="{store:@js($viewModel->statisticsData('store'))}" class="padding">
			<section class="statistics-store scrollbar" :class="response.brandCode">
				<table class="stripes">
					<thead>
						<tr>
							<template x-for="(name, idx) in store.header" :key="idx">
								<th x-text="name"></th>
							</template>
						</tr>
					</thead>
					<tbody>
						<template x-for="(storeData, storeId) in store.data" :key="storeId">
						<tr>
							<td x-text="storeData.areaName"></td>
							<td x-text="storeData.posId"></td>
							<td x-text="storeData.storeKey"></td>
							<td x-text="storeData.storeName"></td>
							<td x-text="Helper.formatDollar(storeData.totalAmount || 0)"></td>
							<td x-text="Helper.formatDollar(storeData.invoiceAmount || 0)"></td>
							<td x-text="Helper.formatDollar(storeData.closingAmount || 0)"></td>
						</tr>
						</template>
					</tbody>
				</table>
			</section>
		</div>
	</div>
	
	<dialog x-data="storeDetail()" @active-store.window="openDetail($event.detail)" id="salesDetail" class="store-detail bottom scroll">
		<div class="row">
			<div class="left-align max">
				<h6 x-text="detail?.storeName || ''" class="purple-text"></h6>
				<div x-text="detail?.storeKey || ''"></div>
			</div>
			<nav class="right-align">
				<button class="transparent circle" @click="ui('#salesDetail');"><i>close</i></button >
			</nav>
		</div>
		
		<table class="stripes responsive">
			<thead>
				<tr>
					<template x-for="(headName, hidx) in detail.header" :key="hidx">
						<th x-text="headName"></th>
					</template>
				</tr>
			</thead>
			<tbody>
				<template x-for="(rowData, rowIdx) in detail.products" :key="rowIdx">
				<tr>
					<template x-for="(colData, colIdx) in ($store.sales.showAmount ? rowData['amount'] : rowData['qty'])" :key="colIdx">
					<td>
						<span x-show="!$store.sales.showAmount || colIdx == 0" x-text="colData"></span>
						<span x-show="$store.sales.showAmount && colIdx > 0" x-text="Helper.formatDollar(Math.round(colData))"></span>
					</td>
					</template>
				</tr>
				</template>
			</tbody>
		</table>
		
	</dialog>
</section>
	