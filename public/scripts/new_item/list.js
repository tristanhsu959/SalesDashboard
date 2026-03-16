/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.store('newItem', {
		tabIndex: Alpine.$persist(0),
	});
	
	Alpine.data('newItemList', (list, brands) => ({
		newItems: list,
		brands: brands,
		activeTab: 0,
		
		init() {
			this.activeTab = Alpine.store('newItem').tabIndex;
			
			if (! this.activeTab)
				this.activeTab = 1;
		},
		confirmDelete(url) {
			Alpine.store('dialog').show('確定要刪除此新品設定?', true, () => this.deleteNewItem(url));
		},
		
		deleteNewItem(url) {
			this.$dispatch('show-loading');
			const form = this.$refs.newItemListForm;
            form.action = url;
            form.submit();
		},
    }));
});

