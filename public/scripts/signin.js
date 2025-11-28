/* Login JS */

$(function(){
	$('#btnSignin').click(function() {
		if (validateForm(['#adAccount', '#adPassword'], true))
			$('#signinForm').submit();
		else
			return false;
	});
	
});