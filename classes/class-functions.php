<?php

/**
 * Display Translation Stats from Transifex
 *
 * @since 1.0
 *
 * @param string $slug Transifex slug
 */
function codepress_the_transifex_stats( $project, $resource = '', $args = array() ) {

	// Trigger scripts; these will be place in the footer.
	Codepress_Transifex::scripts();

	$data_resource = $resource ? " data-resource-slug='{$resource}'" : ''; ?>
	<div class='transifex-stats' data-project-slug='<?php echo $project; ?>'<?php echo $data_resource; ?>/></div>
	<?php
}