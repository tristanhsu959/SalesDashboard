/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('search', (searchData, options) => ({
		searchData: {...searchData},
		options: {...options},
		errors: new Set(),
		
		init() {
			this.$refs.searchDate.max = this.searchData.today;
		},
		
		search() {
			this.errors.clear();
			
			if (this.searchData.date == '')
				this.errors.add('date');
			
			if (this.errors.size == 0)
			{
				this.$store.app.isLoading = true;
				setTimeout(() => {
					ui('#searchPanel');
					this.$el.submit();
				}, 50);
			}
			else
				return false;
		},
		
		resetSearch() {
			this.searchData.date = this.searchData.today;
			this.searchData.storeName = '';
			this.errors.clear();
		},
    }));
	
	Alpine.data('storeList', (statistics) => ({
		statistics: {...statistics},
		formData : {
			storeId: 0,
		},
		
		init() {
			
		},
		
		getDetail(storeId) {
			this.formData.storeId = storeId;
			this.$nextTick(() => {
				this.$refs.detailForm.submit();
			});
		},
    }));
});

