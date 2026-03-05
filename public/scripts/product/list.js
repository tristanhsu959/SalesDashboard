/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('productList', (list, brands) => ({
		products: list,
		brands: brands,
		activeTab: 0,
		
		init() {
			this.activeTab = Alpine.store('tabIndex').product;
			
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
    }));
});

