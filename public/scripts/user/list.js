/* JS */

$(function(){
	$('.btn-delete').click(function(e){
		e.preventDefault();
		let action = $(this).attr('href');
		
		let callback = function(){
			$('#userListForm').attr('action', action).submit();
		};
		
		showConfirmDialog('是否確認刪除?', callback);
	});
	
	$('.btn-search').click(function(e){
		e.preventDefault();
		$('#loading').addClass('active');
		$('#searchForm')[0].submit();
		/*if (validateForm('#searchAd') || validateForm('#searchName') || validateForm('#searchArea'))
			$('#searchForm').submit();
		else
			showAlertDialog('至少須輸入一個條件');*/
	});
	
	$('.btn-search-reset').click(function(e) {
		$('#searchForm').find('.input-field input').val('');
		$('#searchForm').find('select').prop('selectedIndex', 0);
	});
});