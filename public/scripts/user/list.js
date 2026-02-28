/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('searchUser', (searchData) => ({
		searchData: searchData,
		
		search() {
			this.$el.submit();
		},
		
		reset(url) {
			this.searchData.ad = '';
			this.searchData.name = '';
			this.searchData.roleId = 0;
		}
    }));
	
    Alpine.data('userList', () => ({
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

