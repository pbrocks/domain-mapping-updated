jQuery(document).ready(function($) {
	$('#ptb-form').submit(function(e) {
		$('#ptb_loading').show();
		$('#ptb_submit').attr('disabled', true);
		
      data = {
      	action: 'ptb_get_results',
      	ptb_nonce: ptb_vars.ptb_nonce
      };

     	$.post(ajaxurl, jQuery("#ptb-form").serialize()
		,
		function(response){
			jQuery("#ptb-results").html(response);
			console.log (response);
		});
	});
});