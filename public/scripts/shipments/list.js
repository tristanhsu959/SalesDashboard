/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('searchShipments', (searchData, options) => ({
		searchData: {...searchData},
		options: {...options},
		errors: new Set(),
		
		init() {
			
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
			
			if (this.searchData.by == 'keyword' && this.searchData.keyword == '')
				this.errors.add('keyword');
			
			if (this.searchData.by == 'category' && this.searchData.shortCodes.length == 0)
			{
				this.errors.add('shortCodes');
				Alpine.store('toast').notify('請勾選產品');
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
			this.searchData.calc = Object.keys(this.options.mode.calc)[0];
			this.searchData.by = Object.keys(this.options.mode.by)[0];
			this.searchData.stDate = '';
			this.searchData.endDate = '';
			this.searchData.keyword = '';
			this.searchData.category = '';
			this.searchData.shortCodes = [];
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

