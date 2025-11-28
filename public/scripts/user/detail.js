/* Role Create JS */

$(function(){
	$('.btn-cancel').click(function(){
		$('#userForm')[0].reset();
	});
	
	$('.btn-save').click(function(e){
		e.preventDefault();
		submitForm();
	});
});

function submitForm()
{
	if (validateForm(['#adAccount', 'input[name=role]:checked']))
		$('#userForm').submit();
	else
		showAlertDialog('AD帳號，所屬身份為必填');
}