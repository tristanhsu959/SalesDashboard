/* New Release JS */

$(function(){
	$('.btn-search').click(function(e){
		e.preventDefault();
		$('#loading').addClass('active');
		$('#searchForm')[0].submit();
	});
	
	$('.btn-search-reset').click(function(e) {
		$('#searchForm').find('.input-field input').val('');
	});
});