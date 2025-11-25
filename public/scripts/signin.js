/* Login JS */

$(function(){
	$('#btnSignin').click(function() {
		if (validateForm(['#ad_account', '#ad_password']))
			$('#signinForm').submit();
		else
			return false;
	});
	
});