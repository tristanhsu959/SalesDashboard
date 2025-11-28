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
	
	/* Remove invalid style */
	$('.form-control').on('keypress', function(event){
		$(this).removeClass('is-invalid');
	});
	$('.form-select').on('change', function(event){
		$(this).removeClass('is-invalid');
	});
	
	$('.toast').toast('show');
});

/* valid or invalid */
function validateForm(fields)
{
	//el: id/class/ or ....
	if ($.isArray(fields))
	{
		let result = true;
		
		$.each(fields, function(key, el){
			result = result & validateInput(el);
		});
		
		return result;
	}
	else
		return validateInput(els);
}

function validateInput(el)
{
	$(el).removeClass('is-invalid');
	
	if ($(el).val() == '')
	{
		$(el).addClass('is-invalid');
		return false;
	}
	else
		return true;
}

function showConfirmDialog(desc, callback)
{
	$('#confirm_modal .description').text(desc);
	/* 須用on event模式 */
	$('#confirm_modal .btn-major').on('click', callback);
	$('#confirm_modal').modal('show');
}