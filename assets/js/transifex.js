(function($) {

	/** Fire when DOM is ready */
	$(document).ready( function(){

		codepress_transifex();
	});

	function codepress_transifex() {

		if ( 0 === $('.transifex-stats').length ) return;

		$('.transifex-stats').each( function(){

			var container = $(this);

			// add loading icon
			container.addClass('loading');

			// fetch with ajax
			$.ajax({
				url: cpti.ajaxurl,
				data: {
					action: 'transifex_project_stats',
					project_slug: container.attr('data-project-slug'),
					resource_slug: container.attr('data-resource-slug'),
				},
				type: 'post',
				dataType: 'html',
				success: function( html ){
					// error
					if ( ! html ) {
						html = cpti.no_result;
					}

					container.html( html );
				},
				complete: function() {

					// remove loading icon
					container.removeClass('loading');
				}
			});
		});
	}

})(jQuery);