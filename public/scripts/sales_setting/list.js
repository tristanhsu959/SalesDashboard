/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.store('salesSetting', {
		tabIndex: Alpine.$persist(1),
	});
	
	Alpine.data('salesSetting', (settings, options) => ({
		settings: settings,
		products: options.products,
		brands: options.brands,
		activeTab: 0,
		
		init() {
			this.activeTab = Alpine.store('salesSetting').tabIndex;
			
			if (! this.activeTab)
				this.activeTab = 1;
		},
		
    }));
});

