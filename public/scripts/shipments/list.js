/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('searchShipments', (searchData, options) => ({
		searchData: {...searchData},
		options: {...options},
		/* mode: {...options.mode},
		category: {...options.category},
		products: {...options.products}, */
		errors: new Set(),
		
		init() {
			
		},
		
		/* changeProducts(catNo) {
			this.searchData.catNo = catNo;
			this.errors.delete('catNo');
			this.errors.delete('productNo');
		}, */
		
		search() {
			this.errors.clear();
			
			if (this.searchData.stDate == '')
				this.errors.add('stDate');
			if (this.searchData.endDate == '')
				this.errors.add('endDate');
			
			if (this.searchData.stDate && this.searchData.endDate)
			{
				if (new Date(this.searchData.stDate) > new Date(this.searchData.endDate))
				{
					this.errors.add('endDate');
					Alpine.store('toast').notify('結束日期不可小於開始日期');
				}
			}
			
			if (this.searchData.mode == 'name' && this.searchData.productName == '')
				this.errors.add('productName');
			if (this.searchData.mode == 'type' && this.searchData.productType == '')
				this.errors.add('productType');
			
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
			this.searchData.releaseId = '';
			this.searchData.stDate = '';
			this.searchData.endDate = '';
			this.errors.clear();
		},
    }));
});

