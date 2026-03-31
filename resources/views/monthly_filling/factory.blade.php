 
	<section class="factory-list container">
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
			<div class="page padding active" id="page-qty">
				<section class="statistics-factory scrollbar {{$viewModel->getBrandCode()}}">
					<table>
						<thead>
							<tr>
								<th>出貨工廠</th>
								<th>年月</th>
								@foreach($viewModel->statistics['header']['productList'] as $product)
									<th>{{$product['name']}}</th>
								@endforeach
							</tr>
						</thead>
						<tbody>
							@foreach($viewModel->statistics['header']['factoryList'] as $factory)
								<tr>
									<th>{{$factory['factoryName']}}</th>
									@foreach($viewModel->statistics['header']['monthList'] as $month)
										<th>{{$month}}</th>
										@foreach($viewModel->statistics['header']['productList'] as $product)
											<td>{{$viewModel->statistics['data'][$factory['factoryNo']][$month][$product['code']]['qty']}}</td>
										@endforeach
									@endforeach
								</tr>
							@endforeach
						</tbody>
					</table>
				</section>
			</div>
		</div>
		@endif
	</section>
