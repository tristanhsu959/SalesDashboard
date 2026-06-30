/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('search', (searchData) => ({
		searchData: {...searchData.search},
		options: {...searchData.options},
		errors: new Set(),
		
		init() {
			
		},
		
		search() {
			this.errors.clear();
			
			if (this.searchData.stDate == '')
				this.errors.add('stDate');
			
			if (this.searchData.stDate && this.searchData.endDate)
			{
				if (new Date(this.searchData.stDate) > new Date(this.searchData.endDate))
				{
					this.errors.add('endDate');
					Alpine.store('toast').notify('結束日期不可小於開始日期');
				}
			}
			
			if (this.searchData.type == 'area' && this.searchData.areaIds.length == 0)
			{
				this.errors.add('areaIds');
				Alpine.store('toast').notify('請勾選區域');
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
			this.searchData.type = 'all';
			this.searchData.areaId = [];
			this.searchData.storeName = '';
			this.errors.clear();
		},
    }));
	
	Alpine.data('statistics', (statistics) => ({
		header: {...statistics.header},
		data: {...statistics.data},
		
		init() {
		},
		
	}));
});

