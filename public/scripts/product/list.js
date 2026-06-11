/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.store('product', {
		tabIndex: Alpine.$persist(0),
	});
	
	//filter cache
	Alpine.store('productListStore', {
		filter: '',
	});
	
	Alpine.data('productList', (list, brands) => ({
		products: list,
		brands: brands,
		activeTab: 0,
		
		init() {
			this.activeTab = Alpine.store('product').tabIndex;
			
			if (! this.activeTab)
				this.activeTab = 1;
		},
		confirmDelete(url) {
			Alpine.store('dialog').show('確定要刪除此產品?', true, () => this.deleteProduct(url));
		},
		
		deleteProduct(url) {
			this.$dispatch('show-loading');
			const form = this.$refs.productListForm;
            form.action = url;
            form.submit();
		},
		
		//filter
		filterProducts(key) {
			const searchKeyword = Alpine.store('productListStore').filter.toLowerCase();
			
			const list = Object.values(this.products[key]);
			
			const result = list.filter(product => 
				String(product.productName || '').toLowerCase().includes(searchKeyword) ||
				String(product.categoryName || '').toLowerCase().includes(searchKeyword)
			);
			
			return result;
		},
    }));
});

