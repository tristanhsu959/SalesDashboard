/* Login JS */

document.addEventListener('alpine:init', () => {
    Alpine.data('login', (response) => ({
		formData: {...response.formData},
		errors: new Set(),
		isLoading: false,
		
        validate() {
			this.errors.clear();
			
			if (Helper.isEmpty(this.formData.account))
				this.errors.add('account');
			if (Helper.isEmpty(this.formData.password))
				this.errors.add('password');
			
			if (this.errors.size == 0)
				this.$el.submit();
			else
				return false;
		},
		
		reset() {
			this.formData.account = '';
			this.formData.password = '';
			this.errors.clear();
			this.isLoading = false;
		}
    }));
	
	Alpine.data('changePassword', (formData) => ({
		formData: {...formData},
		errors: new Set(),
		
		validate() {
			this.errors.clear();
			
			if (Helper.isEmpty(this.formData.account))
				this.errors.add('account');
			
			if (this.errors.size == 0)
				this.$el.submit();
			else
				return false;
		},
		
		reset() {
			this.formData.account = '';
			this.errors.clear();
			ui('#forgetPwd');
		}
    }));
});
