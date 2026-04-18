/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('salesSettingList', (settings, options) => ({
		settings: settings,
		options: options,
		activeTab: 1,
		
		init() {
			this.activeTab = 1;
		},
    }));
});

