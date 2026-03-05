/* Role Create JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('newItemForm', (formData, options) => ({
		formData: formData,
		options: options,
		products: [],
		errors: new Set(),
		
		init() {
			if (this.formData.brand)
				this.updateProducts();
		},
		
		updateProducts() {
			const selectedBrand = this.formData.brand;
			this.products = this.options.products[selectedBrand] || [];
		},
		
		validate() {
			this.errors.clear();
			
			if (this.formData.brand == 0)
				this.errors.add('brand');
			if (this.formData.productId == 0)
				this.errors.add('productId');
			if (Helper.isEmpty(this.formData.name))
				this.errors.add('name');
			if (Helper.isEmpty(this.formData.saleDate))
				this.errors.add('saleDate');
			
			if (this.errors.size == 0)
			{
				this.$dispatch('show-loading');
				this.$el.submit();
			}
			else
				return false;
		},
		
		reset() {
			this.formData.brand = 1;
			this.formData.productId = 0;
			this.formData.name = '';
			this.formData.saleDate = '';
			this.formData.tasteKeyWord = '';
			this.formData.status = 1;
		}
    }));
});

