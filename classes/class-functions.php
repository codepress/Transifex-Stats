<?php

/**
 * Display Translation progress from Transifex
 *
 * @since 1.0
 *
 * @param string $slug Transifex slug
 */
function transifex_display_translation_progress( $project, $resource = '' ) {
	// Trigger scripts; these will be place in the footer.
	Codepress_Transifex::scripts();
	$stats = new Codepress_Transifex_Stats( $project, $resource, null ); // uses long term cahce
	$data_resource = $resource ? " data-resource-slug='{$resource}'" : ''; ?>
	<div class='transifex-stats' data-project-slug='<?php echo $project; ?>'<?php echo $data_resource; ?>/>
		<?php $stats->display_translations_progress(); ?>
	</div>
	<?php
}

/**
 * Display Translation Stats from Transifex
 *
 * @since 1.0
 *
 * @param string $slug Transifex slug
 */
function transifex_display_translators( $project ) {
	Codepress_Transifex::scripts(); // footer scripts
	$stats = new Codepress_Transifex_Stats( $project, '', null ); // uses long term cahce
	?>
	<div class='transifex-stats-contributors' data-project-slug='<?php echo $project; ?>'/>
		<?php //$stats->display_contributors(); ?>
	</div>
	<?php
}

/**
 * Deprecated
 *
 * @since 1.1
 */
function codepress_the_transifex_stats( $project, $resource = '' ) {
	_deprecated_function( __FUNCTION__, '1.0', 'transifex_display_translation_progress()' );
	return transifex_display_translation_progress( $project, $resource );
}