/* Role Create JS */

$(function(){
	$('.btn-cancel').click(function(){
		$('#userForm')[0].reset();
	});
	
	$('.btn-save').click(function(e){
		e.preventDefault();
		createUser();
	});
	
	// $('#group').change(function(){
		// if ($(this).val() == $('#roleForm').data('admin'))
		// {
			// $('.role-permission ul.admin .form-check-input').prop('disabled', false);
			// $('.role-permission ul.admin').show();
		// }
		// else
		// {
			// $('.role-permission ul.admin .form-check-input').prop('disabled', true);
			// $('.role-permission ul.admin').hide();
		// }
	// }).trigger('change');
	
	// $('.permission-group .form-check-input').change(function(){
		// let checkAll = false;
		
		// if ($(this).is(':checked'))
			// checkAll = true;
		
		// $(this).closest('.list-group-item').find('.permission-group-items .form-check-input').each(function(){
			// $(this).prop('checked', checkAll);
		// });
	// });
	
	// $('.permission-group-items .form-check-input').change(function(){
		// if ($(this).closest('.permission-group-items').find('.form-check-input:checked').length == $(this).closest('.permission-group-items').find('.form-check-input').length)
			// $(this).closest('.list-group-item').find('.permission-group .form-check-input').prop('checked', true);
		// else
			// $(this).closest('.list-group-item').find('.permission-group .form-check-input').prop('checked', false);
	// }).trigger('change');
});

function createUser()
{
	if (validateForm(['#adAccount', 'input[name=role]:checked']))
		$('#userForm').submit();
	else
		showAlertDialog('AD帳號，所屬身份為必填');
}