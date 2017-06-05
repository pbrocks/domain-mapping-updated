jQuery(document).ready( function($) {

	$.ajax({
		url: "/",
		success: function( data ) {
			alert( 'Your home page has ' + $(data).find('div').length + ' div elements.');
		}
	})

})
