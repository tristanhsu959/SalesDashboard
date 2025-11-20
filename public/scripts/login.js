/* Login JS */

$(function(){
	$('#btnLogin').click(function() {
		if (validationInput('#ad_account') && validationInput('#ad_password'))
			$('#loginForm').submit();
		else
			return false;
	});
	
});