/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('search', (searchData) => ({
		searchData: {...searchData.search},
		options: {...searchData.options},
		errors: new Set(),
		
		init() {
			if (this.searchData.stDate == '')
				this.searchData.stDate = this.searchData.today;
			if (this.searchData.endDate == '')
				this.searchData.endDate = this.searchData.today;
			this.switchConditions();
		},
		
		switchConditions(){
			if (this.searchData.type == 'store')
			{
				this.$refs.searchStDate.type = 'date'; //input type
				this.$refs.searchEndDate.type = 'date';
				this.$refs.searchShopName.disabled = false;
				this.searchData.stDate = this.searchData.today;
				this.searchData.endDate = this.searchData.today;
			}
			else //aov
			{
				this.$refs.searchStDate.type = 'month';
				this.$refs.searchEndDate.type = 'month';
				this.$refs.searchShopName.disabled = true;
				this.searchData.stDate = this.searchData.thisMonth;
				this.searchData.endDate = this.searchData.thisMonth;
			}
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
			this.searchData.type = 'store';
			this.searchData.shopName = '';
			this.errors.clear();
			this.switchConditions();
		},
    }));
	
	Alpine.data('aovStatistics', (statistics) => ({
		statisticsData: {...statistics.data},
		expansion: new Set(),
		
		init() {
			this.expansion.clear();;
		},
		
		addExpansion(typeKey, month) {
			const key = `${typeKey}-${month}`;
			
			if (this.expansion.has(key))
				this.expansion.delete(key);
			else
				this.expansion.add(key);
		},
		
		showDetail(typeKey, month) {
			const key = `${typeKey}-${month}`;
			
			return this.expansion.has(key);
		},
		
    }));
});

