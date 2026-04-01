 
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
								<template x-for="(header, idx) in statistics.header.storeHeader" :key="idx">
									<th x-text="header"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(store, storeId) in statistics.header['storeList']" :key="storeId">
							<tr>
								<th x-text="store.postId"></th>
								<th x-text="store.area"></th>
								<th x-text="store.storeName"></th>
								<template x-for="(month, idx) in statistics.header.monthList" :key="idx">
									<td x-text="statistics.data[groupId]?.[storeId]?.[month]?.['qty'] ?? 0"></td>
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
