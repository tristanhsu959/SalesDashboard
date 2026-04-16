 
	<section x-data='statisticsFactory(@json($viewModel->statistics))' class="factory-list container">
		@if($viewModel->isDataEmpty())
		<article class="error-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
		@else
		<div class="factory-content">
			<div class="tabs cyan-text">
				<template x-for="(name, id) in statistics.header['productList']" :key="id">
					<a x-text="name" @click="activeProduct = id" :class="{ 'active': activeProduct === id }"></a>
				</template>
			</div>
			
			<!-- 工廠 -->
			<template x-for="(name, productId) in statistics.header['productList']" :key="productId">
			<div class="page padding" :class="{ 'active': activeProduct === productId }">
				<section class="statistics-factory scrollbar {{$viewModel->getBrandCode()}}">
					<table>
						<thead>
							<tr>
								<th>出貨工廠</th>
								<template x-for="(name, id) in statistics.header['dateList']" :key="id">
									<th x-text="name"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(factoryName, factoryId) in statistics.header['factoryList']" :key="factoryId">
							<tr>
								<th x-text="factoryName"></th>
								<template x-for="(date, idx) in statistics.header.dateList" :key="idx">
									<td x-text="statistics.data[productId]?.[factoryId]?.[date]?.['qty'] ?? 0"></td>
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
