/* Common JS */

$(function(){
	/* Menu */
	$('.menu ul.list-group li a').each(function($item, $key){
		if ($(this).hasClass('active'))
		{
			$(this).closest('.collapse').collapse('show');
			$(this).closest('.menu-group').find('a.list-title').attr('aria-expanded', 'true');
		}
	});
	
	$('.toast').toast('show');
});

/* valid or invalid */
function validationInput(el)
{
	//el: id/class/ or ....
	$(el).removeClass('is-valid is-invalid');
	
	if ($(el).val() == '')
	{
		$(el).addClass('is-invalid');
		return false;
	}
	else
	{
		$(el).addClass('is-valid');
		return true;
	}
}