/* Login JS */

$(function(){
	$('#btnSignin').click(function() {
		if (validateForm(['#adAccount', '#adPassword'], true))
			$('#signinForm').submit();
		else
			return false;
	});
	
	$('#adPassword').on('keydown', function(e) {
		if (e.which === 13) 
		{
			if (validateForm(['#adAccount', '#adPassword'], true))
				$('#signinForm').submit();
			else
				return false;
		}
    });
});