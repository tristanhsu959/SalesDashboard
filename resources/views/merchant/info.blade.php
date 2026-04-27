 
	<section x-data='storeInfo(@json($viewModel->statistics))' class="store-info container">
		@if($viewModel->isDataEmpty())
		<article class="error-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
		@else
		<div class="store-content">
			<!-- 門店 -->
			<section class="statistics-store scrollbar {{$viewModel->getBrandCode()}}">
				<table class="stripes">
					<thead>
						<tr>
							<template x-for="(name, idx) in statistics.info.header" :key="idx">
								<th x-text="name"></th>
							</template>
						</tr>
					</thead>
					<tbody>
						<template x-for="(store, idx) in statistics.info.store" :key="idx">
						<tr>
							<template x-for="(row, rowIdx) in store" :key="rowIdx">
								<td x-text="row"></td>
							</template>
						</tr>
						</template>
					</tbody>
				</table>
			</section>
		</div>
		@endif
	</section>
