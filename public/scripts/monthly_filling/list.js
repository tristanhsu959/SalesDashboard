/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('searchReport', (searchData, options) => ({
		searchData: {...searchData},
		options: {...options},
		errors: new Set(),
		
		init() {
			
		},
		
		search() {
			this.errors.clear();
			
			if (this.searchData.stMonth == '')
				this.errors.add('stMonth');
			if (this.searchData.endMonth == '')
				this.errors.add('endMonth');
			
			if (this.searchData.stMonth && this.searchData.endMonth)
			{
				if (new Date(this.searchData.stMonth) > new Date(this.searchData.endMonth))
				{
					this.errors.add('endMonth');
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
			this.searchData.stMonth = '';
			this.searchData.endMonth = '';
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

