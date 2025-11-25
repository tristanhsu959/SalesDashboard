/* Role Create JS */

$(function(){
	$('.btn-cancel').click(function(){
		$('#roleForm')[0].reset();
	});
	
	$('.btn-save').click(function(){
		createRole();
	});
	
	$('.role-permission .permission-group .form-check-input').change(function(){
		let checkAll = false;
		
		if ($(this).is(':checked'))
			checkAll = true;
		
		$(this).closest('.list-group-item').find('.permission-group-items .form-check-input').each(function(){
			$(this).prop('checked', checkAll);
		});
	});
});

function createRole()
{
	//沒有設定權限也可以Submit
	if (validateForm(['#name', '#group']))
		$('#roleForm').submit();
	else
		return false;
}