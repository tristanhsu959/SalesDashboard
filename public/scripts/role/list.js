/* JS */

document.addEventListener('alpine:init', () => {
    Alpine.data('roleList', () => ({
		confirmDelete(url) {
			Alpine.store('dialog').show('確定要刪除此身份', true, () => this.deleteRole(url));
		},
		
		deleteRole(url) {
			const form = this.$refs.roleListForm;
            form.action = url;
            form.submit();
		}
    }));
});

/* $(function(){
	$('.btn-list-delete').click(function(e){
		e.preventDefault();
		let action = $(this).attr('href');
		
		let callback = function(){
			$('#roleListForm').attr('action', action).submit();
		};
		
		showConfirmDialog('是否確認刪除?', callback);
	});
}); */