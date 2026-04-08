/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('purchaseSettingList', (settings, options) => ({
		settings: settings,
		options: options,
		activeTab: 1,
		
		init() {console.log(this.options);
			this.activeTab = 1;
		},
    }));
});

