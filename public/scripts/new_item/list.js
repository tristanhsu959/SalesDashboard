/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('newItemList', (list, options) => ({
		products: list.products,
		settings: list.settings,
		options: options,
		activeTab: 1,
		
		confirmDelete(url) {
			Alpine.store('dialog').show('確定要刪除此產品?', true, () => this.deleteProduct(url));
		},
		
		deleteProduct(url) {
			this.$dispatch('show-loading');
			const form = this.$refs.productListForm;
            form.action = url;
            form.submit();
		},
		
		getIcon(status) {
			return (status) ? 'active' : 'inactive';
		}
    }));
});

