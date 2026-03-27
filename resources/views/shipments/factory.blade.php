 
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
			<div class="page padding active">
				<section>
					<div class="grid header">
						<div class="s2">出貨工廠</div>
						<template x-for="(name, id) in statistics.header['dateList']" :key="id">
							<div class="s2" x-text="name"></div>
						</template>
					</div>
					
					<template x-for="(factoryName, factoryId) in statistics.header['factoryList']" :key="factoryId">
						<div class="grid data">
							<div class="s2" x-text="factoryName"></div>
							<template x-for="(date, idx) in statistics.header.dateList" :key="idx">
								<div class="s2" x-text="statistics.data[activeProduct]?.[factoryId]?.[date]?.['qty'] ?? 0"></div>
							</template>
						</div>
					</template>
				</section>
			</div>
		</div>
		@endif
	</section>
