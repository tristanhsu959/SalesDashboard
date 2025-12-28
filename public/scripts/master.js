/* Common JS */

$(function(){
	$(document).on('contextmenu', function(e){
		e.preventDefault();
	});
	
	/* Menu */
	$('.menu .menu-group').each(function($item, $key){
		$(this).find('a.list-title').removeClass('active');
		
		$(this).find('.list-group li a').each(function($item, $key){
			if ($(this).hasClass('active'))
			{
				$(this).closest('.collapse').collapse('show');
				$(this).closest('.menu-group').find('a.list-title').addClass('active');
			}
		});
	});
	
	$('.menu .menu-group .list-group-item a').click(function(){
		$('#loading').addClass('active');
	});
	
	/* Remove invalid style */
	$('.form-control').on('keypress', function(event){
		$(this).removeClass('is-invalid');
	});
	$('.form-select').on('change', function(event){
		$(this).removeClass('is-invalid');
	});
	
	$('.toast').toast('show');
	
	var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
		var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl)
	})
});

/* valid or invalid */
function validateForm(fields, invalidStyle)
{
	//el: id/class/ or ....
	if ($.isArray(fields))
	{
		let result = true;
		
		$.each(fields, function(key, el){
			result = result & validateInput(el, invalidStyle);
		});
		
		return Boolean(result);
	}
	else
		return validateInput(fields, invalidStyle);
}

function validateInput(el, invalidStyle)
{
	invalidStyle = invalidStyle || false;
	
	if (invalidStyle)
		$(el).removeClass('is-invalid');
	
	if ($(el).val() == '' || typeof $(el).val() == 'undefined')
	{
		if (invalidStyle)
			$(el).addClass('is-invalid');
		return false;
	}
	else
		return true;
}

/* Dialog */
function showAlertDialog(desc)
{
	$('#alert_modal .description').text(desc);
	$('#alert_modal').modal('show');
}

function showConfirmDialog(desc, callback)
{
	$('#confirm_modal .description').text(desc);
	/* 須用on event模式 */
	$('#confirm_modal .btn-major').on('click', callback);
	$('#confirm_modal').modal('show');
}