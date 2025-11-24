/* Role Create JS */

$(function(){
	$('.btn-cancel').click(function(){
		$('#roleForm')[0].reset();
	});
	
	$('.btn-save').click(function(){
		createRole();
	});
});

function createRole()
{
	if (validationInput('#name') && validationInput('#group'))
			$('#signinForm').submit();
		else
			return false;
	if ($('#name').val() == '' || )
}