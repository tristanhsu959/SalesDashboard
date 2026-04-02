 
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
				<template x-for="(productName, groupId) in statistics.header.sheet" :key="groupId">
					<a x-text="productName" @click="activeProduct = groupId" :data-ui="`#page-${groupId}`" :class="activeProduct == groupId ? 'active':''"></a>
				</template>
			</div>
			
			<!-- 門店 -->
			<template x-for="(productName, groupId) in statistics.header.sheet" :key="groupId">
			<div class="page padding" :id="`page-${groupId}`" >
				<section class="statistics-store scrollbar {{$viewModel->getBrandCode()}}">
					<table class="stripes">
						<thead>
							<tr>
								<template x-for="(header, idx) in statistics.header.tableHeader" :key="idx">
									<th x-text="header"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(rowData, rowDataIndex) in statistics.data[groupId]" :key="rowDataIndex">
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
