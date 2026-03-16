/* JS */

document.addEventListener('alpine:init', () => {
	
	Alpine.data('salesSetting', (settings, options) => ({
		settings: settings,
		products: options.products,
		brands: options.brands,
		activeTab: 1,
		
		init() {
			console.log(this.settings);
		},
    }));
});

