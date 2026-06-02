/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('statistics', (statistics) => ({
		statistics: {...statistics},
		
		init() {
			
		},
    }));
});

