/* Role Create JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('newItemForm', (formData, options) => ({
		formData: formData,
		options: options,
		errors: new Set(),
		
		init() {
			//formData.brand = Number(formData.brand);
			const selectEl = document.querySelector('select[name=productId]');
			selectEl.value = "5"; // 設定目標值
			selectEl.dispatchEvent(new Event('input', { bubbles: true })); 
		},
		
		get productSettings() {
			return this.formData.brand ? this.options.products[this.formData.brand] : [];
		},
		
		validate() {
			this.errors.clear();
			
			if (Helper.isEmpty(this.formData.name))
				this.errors.add('name');
			if (this.formData.brand == 0)
				this.errors.add('brand');
			if (this.formData.primaryNo == '')
				this.errors.add('primaryNo');
			
			if (this.errors.size == 0)
			{
				this.$dispatch('show-loading');
				this.$el.submit();
			}
			else
				return false;
		},
		
		reset() {
			this.formData.name = '';
			this.formData.brand = 0;
			this.formData.primaryNo = '';
			this.formData.secondaryNo = '';
			this.formData.tasteNo = '';
			this.formData.status = 1;
		}
    }));
});

