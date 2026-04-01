 
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
				<a data-ui="#page-qty" class="active">月總量</a>
				<a data-ui="#page-avg">月均量</a>
			</div>
			
			<!-- 工廠 -->
			<template x-for="(data, type) in statistics.data" :key="type">
			<div class="page padding active" :id="`page-${type}`">
				<section class="statistics-factory scrollbar {{$viewModel->getBrandCode()}}">
					<table>
						<thead>
							<tr>
								<template x-for="header in statistics.header">
									<th x-text="header"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(rows, idx) in data" :key="idx">
								<tr>
									<template x-for="row in rows">
										<td x-text="row"></td>
									</template>
								</tr>
							</template>
						</tbody>
					</table>
				</section>
			</div>
			</template>
			
			<!--div class="page padding" id="page-avg">
				<section class="statistics-factory scrollbar {{$viewModel->getBrandCode()}}">
					<table>
						<thead>
							<tr>
								<th>出貨工廠</th>
								<th>年月</th>
								<template x-for="product in statistics.header.productList">
									<th x-text="product.name"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="factory in statistics.header.factoryList" :key="factory.factoryNo">
								<template x-for="month in statistics.header.monthList" :key="month">
									<tr>
										<th x-text="factory.factoryName"></th>
										<th x-text="month"></th>
										<template x-for="product in statistics.header.productList" :key="product.code">
											<td x-text="statistics.data[factory.factoryNo]?.[month]?.[product.code]?.avg || 0"></td>
										</template>
									</tr>
								</template>
							</template>
						</tbody>
					</table>
				</section>
			</div-->
		</div>
		@endif
	</section>
