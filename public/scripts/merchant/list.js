/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('search', (searchData, options) => ({
		searchData: {...searchData},
		options: {...options},
		errors: new Set(),
		
		init() {
			if (searchData.stDate == '')
				this.searchData.stDate = searchData.today;
		},
		
		search() {
			this.errors.clear();
			
			if (this.searchData.type == 'dayOff' && this.searchData.stDate == '')
				this.errors.add('stDate');
			
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
			this.searchData.type = 'info';
			this.searchData.stDate = searchData.today;
			this.errors.clear();
		},
    }));
	
	Alpine.data('storeInfo', (data) => ({
		statistics: {...data},
		
		init() { 
		},
    }));
	
	Alpine.data('storeDayoff', (data) => ({
		statistics: {...data},
		
		init() { 
		},
    }));
});

