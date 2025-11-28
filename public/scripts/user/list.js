/* JS */

$(function(){
	$('.btn-del').click(function(e){
		e.preventDefault();
		let action = $(this).attr('href');
		
		let callback = function(){
			$('#userListForm').attr('action', action).submit();
		};
		
		showConfirmDialog('是否確認刪除?', callback);
	});
	
	$('.btn-search').click(function(e){
		e.preventDefault();
		
		if (validateForm('#searchAd') || validateForm('#searchName') || validateForm('#searchArea'))
			$('#searchForm').submit();
		else
			showAlertDialog('至少須輸入一個條件');
	});
	
});