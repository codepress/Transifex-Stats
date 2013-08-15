(function($) {

	/** Fire when DOM is ready */
	$(document).ready( function(){

		codepress_transifex();
	});

	function codepress_transifex() {

		$('.transifex-stats').each( function(){

			var container = $(this);

			container.addClass('loading');

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
					if ( html ) {
						container.html( html );
					}
					else {}
				},
				complete: function() {
					container.removeClass('loading');
				}
			});
		});
	}

})(jQuery);