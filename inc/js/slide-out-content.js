jQuery( document ).ready( function( $ ){
	$(".nav-side .nav-toggle").on("click", function(e) {
		e.preventDefault();
		$(this).parent().toggleClass("nav-open");
	});
	$(".trigger-side").on("click", function(e) {
		e.preventDefault();
		$(".nav-side").toggleClass("nav-open");
	});
});

