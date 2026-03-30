/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('searchReport', (searchData, options) => ({
		searchData: {...searchData},
		options: {...options},
		errors: new Set(),
		
		init() {
			this.changeDateInput();
		},
		
		changeDateInput(){
			this.$refs.searchStDate.type = this.searchData.range;
			this.$refs.searchEndDate.type = this.searchData.range;
			this.searchData.stDate = '';
			this.searchData.endDate = '';
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
			this.searchData.range = Object.keys(this.options.mode.range)[0];
			this.searchData.stDate = '';
			this.searchData.endDate = '';
			this.errors.clear();
		},
    }));
	
	//Factory
	Alpine.data('statisticsFactory', (data) => ({
		statistics: {...data},
		activeProduct: '',
		
		init() { 
			const keys = Object.keys(this.statistics.header.productList);
			if (keys.length > 0)
				this.activeProduct = keys[0];
		},
    }));
	
	//Store
	Alpine.data('statisticsStore', (data) => ({
		statistics: {...data},
		activeProduct: '',
		
		init() { 
			const keys = Object.keys(this.statistics.header.productList);
			if (keys.length > 0)
				this.activeProduct = keys[0];
		},
    }));
});

