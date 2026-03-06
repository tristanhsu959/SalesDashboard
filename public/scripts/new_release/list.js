/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('searchProduct', (searchData, options) => ({
		searchData: searchData,
		options: options,
		errors: new Set(),
		
		init() {
			
		},
		
		search() {
			this.errors.clear();
			
			if (this.searchData.newItemId == 0)
				this.errors.add('newItemId');
			
			if (this.searchData.stDate == '')
				this.errors.add('stDate');
			
			if (this.searchData.stDate && this.searchData.endDate)
				if (new Date(this.searchData.stDate) > new Date(this.searchData.endDate))
				{
					this.errors.add('endDate');
					Alpine.store('toast').notify('結束日期不可小於開始日期');
				}
				
			if (this.errors.size == 0)
			{
				this.$dispatch('show-loading');
				this.$el.submit();
			}
			else
				return false;
		},
		
		initSearchStDate(newItemId) {
			const minDate = this.options.newItems[newItemId].saleDate;
			this.$refs.searchStDate.min = minDate;
			this.$refs.searchEndDate.min = minDate;
			
			this.searchData.stDate = minDate; //用$refs...value無法連動
			this.errors.delete('stDate');
		},
		
		resetSearch() {
			this.searchData.newItemId = '';
			this.searchData.stDate = '';
			this.searchData.endDate = '';
			this.errors.clear();
		},
    }));
});

