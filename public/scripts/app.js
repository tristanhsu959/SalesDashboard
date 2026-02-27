/* App JS */

document.addEventListener('alpine:init', () => {
	
	Alpine.store('toast', {
		initialize(msg = '') {
            if (msg != '') {
                // 延遲一小段時間確保 DOM 與 BeerCSS 完全就緒
				setTimeout(() => {
                    this.notify(msg);
                }, 100);
            }
        },
		
		notify(message, status = false) {
			if (window.ui) {
				const el = document.querySelector('#notifyMsg');
				const msgEl = el.querySelector('.message');
				const colorClass = (status == true) ? 'green' : 'error';
				
				//Set or reset to empty
				el.classList.remove('green', 'error', 'white-text');
				msgEl.innerText = message;
				el.classList.add(colorClass, 'white-text');
				
				if (message != '')
					ui('#notifyMsg');
			}
		},
	});
	
	Alpine.store('dialog', {
		title: '',
		icon: '',
		color: 'red',
		message: '',
		isConfirm: false,
		callback: false,

		show(message, isConfirm = false, callback = false) {
			this.title = isConfirm ? 'CONFIRM' : 'ALERT';
			this.icon = isConfirm ? 'verified' : 'release_alert';
			this.color = isConfirm ? 'blue-text' : 'red-text';
			this.message = message;
			this.isConfirm = isConfirm;
			this.callback = callback;
			
			ui('#modal-dialog');
		},

		confirm() {
			if (this.callback) 
				this.callback();
			
			ui('#modal-dialog');
		}
	});
});
