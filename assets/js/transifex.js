(function($) {

	/** Fire when DOM is ready */
	$(document).ready( function(){

		codepress_transifex_translations();
		//codepress_transifex_contributors();
	});

	function codepress_transifex_translations() {

		if ( 0 === $('.transifex-stats').length ) {
			return;
		}

		$('.transifex-stats').each( function(){

			var container = $(this).addClass('loading');

			$.ajax({
				url: cpti.ajaxurl,
				data: {
					action: 'transifex_project_stats',
					project_slug: container.data('project-slug'),
					resource_slug: container.data('resource-slug'),
				},
				type: 'post',
				dataType: 'html',
				success: function( html ){

					if ( html && '0' !== html ) {
						container.html( html );
					}
				},
				complete: function() {
					container.removeClass('loading');
				}
			});
		});
	}

	function codepress_transifex_contributors() {

		if ( 0 === $('.transifex-stats-contributors').length ) {
			return;
		}

		$('.transifex-stats-contributors').each( function(){

			var container = $(this).addClass('loading');

			$.ajax({
				url: cpti.ajaxurl,
				data: {
					action: 'transifex_contributor_stats',
					project_slug: container.attr('data-project-slug')
				},
				type: 'post',
				dataType: 'html',
				success: function( html ){

					if ( html && '0' !== html ) {
						container.html( html );
					}
				},
				complete: function() {
					container.removeClass('loading');
				}
			});
		});
	}

})(jQuery);