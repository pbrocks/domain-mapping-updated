function send_email(){
	jQuery.post(ptb_email_script.ajaxurl, jQuery("#ptb_submit").serialize()
		,
		function(response){
			jQuery("#ptb_response_area").html(response);
		}
	);
}
