/* JS */

$(function(){
	$('.btn-del').click(function(e){
		e.preventDefault();
		let action = $(this).attr('href');
		
		let callback = function(){
			$('#roleListForm').attr('action', action).submit();
		};
		
		showConfirmDialog('是否確認刪除?', callback);
	});
});