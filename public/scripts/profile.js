/* Role Create JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('userProfile', (profile, options) => ({
		profile: {...profile},
		options: options,
		
		init() {
			
		},
    }));
	
	Alpine.data('userProfileEdit', (formData, options) => ({
		profile: structuredClone(formData), //deep copy
		formData: {...formData},
		options: options,
		errors: new Set(),
		fieldEnabled: [],
		showPassword: false,
		
		init() {
			this.formData.userPassword = '';
			this.formData.passwordOnly = '';
			this.showPassword = false;
			this.initFieldEnabled();
		},
		
		initFieldEnabled(){
			this.fieldEnabled.push('displayName');
			this.fieldEnabled.push('department');
			this.fieldEnabled.push('email');
			this.fieldEnabled.push('password');
		},
		
		validate() {
			this.errors.clear();
			/* const inputElement = document.querySelector('#myInput');

			if (!inputElement.disabled) {
				console.log("輸入框已啟用，值為:", inputElement.value);
			} else {
				console.log("輸入框目前被禁用 (disabled)");
			} */
			
			if (this.formData.passwordOnly == '0')
			{
				if (Helper.isEmpty(this.formData.userDisplayName))
					this.errors.add('displayName');
				if (Helper.isEmpty(this.formData.department))
					this.errors.add('department');
				if (Helper.isEmpty(this.formData.email))
					this.errors.add('email');
				if (Helper.isEmpty(this.formData.userPassword))
					this.errors.add('password');
				
				if (this.errors.size == 0)
				{
					this.$dispatch('show-loading');
					this.$el.submit();
				}
				else
					return false;
			}
						
			const pattern = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/;
			
			if (! Helper.isEmpty(this.formData.password) && ! pattern.test(this.formData.password)) 
			{
				this.errors.add('password');
				Alpine.store('toast').notify('密碼須包含英數，6個字元以上');
			}
			
			if (this.errors.size == 0)
			{
				this.$dispatch('show-loading');
				this.$el.submit();
			}
			else
				return false;
		},
		
		reset() {
			this.formData.userPassword = '';
			this.formData.userDisplayName = this.profile.userDisplayName;
			this.formData.department = this.profile.department;
			this.formData.email = this.profile.email;
			this.initFieldEnabled();
		}
    }));
});

