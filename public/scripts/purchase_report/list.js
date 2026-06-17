/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('searchReport', (searchData) => ({
		searchData: {...searchData.search},
		options: {...searchData.options},
		errors: new Set(),
		
		init() {
			this.searchData.stDate = this.searchData.today;
			this.searchData.endDate = this.searchData.today;
		},
		
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
			this.searchData.type = Object.keys(this.options.mode.type)[0];
			this.searchData.stDate = this.searchData.today;
			this.searchData.endDate = this.searchData.today;
			this.errors.clear();
		},
    }));
	
	//營運概況
	Alpine.data('statisticsPerformance', (data) => ({
		statistics: {...data},
		activeSheet: '',
		
		init() { 
			const keys = Object.keys(this.statistics.report.sheets);
			
			if (keys.length > 0)
				this.activeSheet = keys[0];
			
			this.$nextTick(() => ui(`#page-${this.activeSheet}`));
		},
    }));
});

