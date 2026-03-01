/* Role Create JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('productForm', (formData) => ({
		formData: formData,
		hasSecondaryNo: false,
		errors: new Set(),
		
		init() {
			this.initErpNoInput();
		},
		
		initErpNoInput() {
			this.errors.delete('brand');
			this.hasSecondaryNo = (this.formData.brand == formData.buygoodId);
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
			this.formData.secondaryNo = ''
		}
    }));
});

