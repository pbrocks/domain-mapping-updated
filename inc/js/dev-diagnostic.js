jQuery(document).ready(function($){
	$("#pbrx-trigger").click(function(e) {
		$( ".salmon-js" ).toggleClass("hidden");
	});
	$('.toggle-button').click(function(e) {
		$('#toggle-target').toggle('fast');
		$('.toggle-target').delay( 2300 )
	});
	$('#toggle-event').change(function(e) {
		$('#console-event').html('Toggle: ' + $(this).prop('checked'))
	}); 
});