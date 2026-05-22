 
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
				<template x-for="(sheetName, sheetId) in statistics.sheets" :key="sheetId">
					<a x-text="sheetName" @click="activeProduct = sheetId" :data-ui="`#page-${sheetId}`" :class="activeProduct == sheetId ? 'active':''"></a>
				</template>
			</div>
			
			<!-- 門店 -->
			<template x-for="(sheetName, sheetId) in statistics.sheets" :key="sheetId">
			<div class="page padding" :id="`page-${sheetId}`" >
				<section class="statistics-shop scrollbar" :class="statistics.brandCode">
					<table class="stripes">
						<thead>
							<tr>
								<template x-for="(header, idx) in statistics.header" :key="idx">
									<th x-text="header"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(rowData, rowDataIndex) in statistics.data[sheetId]" :key="rowDataIndex">
							<tr>
								<template x-for="(row, idx) in rowData" :key="idx">
									<td x-text="row"></td>
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
