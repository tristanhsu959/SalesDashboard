@extends('layouts.app')
@use('App\Enums\Brand')

@push('styles')
    <!--link href="{{ asset('styles/product/list.css') }}" rel="stylesheet"-->
@endpush

@push('scripts')
    <script src="{{ asset('scripts/product/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Content -->
@if($viewModel->status() === TRUE)

	<header class="page-nav" :class="isTop ? 'blue-grey10' : 'orange'">
		<nav>
			<a href="{{ route('product.create') }}" class="btn-create button circle"><i>add</i></a>
		</nav>
	</header>
	
	<form x-data="productList" action="" method="post" x-ref="productListForm">
		@csrf
		<section class="product-list container">
			@if(empty(($viewModel->list)))
			<article class="error-container border">
				<div class="row">
					<i>info</i><div class="max">查無符合資料</div>
				</div>
			</article>
			@else
			<table class="stripes border odd-blue">
				<thead>
					<tr>
						<th class="min">#</th>
						<th>品牌</th>
						<th>產品名稱</th>
						<th>狀態</th>
						<th class="right-align">操作</th>
					</tr>
				</thead>
				<tbody>
				@foreach($viewModel->list as $idx => $product)
					<tr>
						<td>{{ $idx + 1 }}</td>
						<td>{{ Brand::tryFrom($product['productBrand'])->label() }}</td>
						<td>{{ $product['productName'] }}</td>
						<td><i x-data="{status: @json($product['productStatus'])}" x-text="status ? 'check_circle':'x_circle'" :class="status ? 'green-text fill':'red-text fill'"></i></td>
						<td class="right-align action">
							<a href="{{ route('product.update', [$product['productId']]) }}" class="btn-edit button circle small">
								<i class="small">edit</i>
							</a>
							<a @click.prevent="confirmDelete($el.href)" href="{{ route('product.delete', [$product['productId']]) }}" class="btn-delete button circle small">
								<i class="small">delete</i>
							</a>
						</td>
					</tr>
				@endforeach
				</tbody>
			</table>
			@endif
		</section>
	</form>

@endif
<!-- Content -->
@endsection