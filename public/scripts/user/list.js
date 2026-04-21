/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('searchUser', (searchData, options) => ({
		searchData: {...searchData},
		options: {...options},
		
		search() {
			this.$el.submit();
		},
		
		reset() {
			this.searchData.ad = '';
			this.searchData.name = '';
			this.searchData.roleId = 0;
		}
    }));
	
    Alpine.data('userList', (list, options) => ({
		list: list,
		options: {...options},
		
		init(){},
		confirmDelete(url) {
			Alpine.store('dialog').show('確定要刪除此帳號?', true, () => this.deleteUser(url));
		},
		
		deleteUser(url) {
			this.$dispatch('show-loading');
			const form = this.$refs.userListForm;
            form.action = url;
            form.submit();
		}
    }));
});

