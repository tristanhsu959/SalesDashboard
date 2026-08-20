/* Login JS */

document.addEventListener('alpine:init', () => {
    Alpine.data('login', (response) => ({
		formData: {...response.formData},
		errors: new Set(),
		isLoading: false,
		isForgetPassword: false,
		showForgetPasswordButton: false,
		showOidcButton: false,
		
		get showPassword(){
			return ! this.isForgetPassword;
		},
		get showPasswordHint(){
			return this.isForgetPassword;
		},
		get showSubmitButton(){
			return ! this.isForgetPassword;
		},
		get showSendButton(){
			return this.isForgetPassword;
		},
		
		validate() {
			if (this.isForgetPassword)
				this.validateForgetPassword();
			else
				this.validateLogin();
		},
		
        validateLogin() {
			this.errors.clear();
			
			if (Helper.isEmpty(this.formData.account))
				this.errors.add('account');
			if (Helper.isEmpty(this.formData.password))
				this.errors.add('password');
			
			if (this.errors.size == 0)
			{
				this.$el.action = this.formData.formAction;
				this.$el.submit();
			}
			else
				return false;
		},
		
		validateForgetPassword() {
			this.errors.clear();
			
			if (Helper.isEmpty(this.formData.account))
				this.errors.add('account');
			
			if (this.errors.size == 0)
			{
				this.$el.action = this.formData.forgetPasswordAction;
				this.$el.submit();
			}
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
});
