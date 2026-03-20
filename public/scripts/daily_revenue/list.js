/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('search', (searchData, options) => ({
		searchData: {...searchData},
		options: {...options},
		errors: new Set(),
		
		init() {
			if (searchData.stDate == '')
				this.searchData.stDate = searchData.today;
			if (searchData.endDate == '')
				this.searchData.endDate = searchData.today;
		},
		
		search() {
			this.errors.clear();
			
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
			this.searchData.stDate = searchData.today;
			this.searchData.endDate = searchData.today;
			this.errors.clear();
		},
    }));
});

