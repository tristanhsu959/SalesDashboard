	<!-- 區域彙總 -->
	<div x-data="{area:@js($viewModel->statisticsData('area'))}" class="page padding active" id="tab-area">
		<section class="statistics-area">
			<table>
				<thead>
					<tr>
						<th x-text="area.header.areaName"></th>
						<th x-text="area.header.shopCount"></th>
						<template x-for="(date, dateKey) in area.header.dayAmount" :key="dateKey">
							<th x-text="date"></th>
						</template>
					</tr>
				</thead>
				<tbody>
					<template x-for="(areaData, areaId) in area.data" :key="areaId">
					<tr>
						<td x-text="areaData.areaName"></td>
						<td x-text="areaData.shopCount"></td>
						<template x-for="(date, dateKey) in area.header.dayAmount" :key="dateKey">
							<td x-text="'$'+ (areaData.dayAmount[date] || 0)"></td>
						</template>
					</tr>
					</template>
				</tbody>
			</table>
		</section>
	</div>