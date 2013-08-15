<?php

/**
 * Display Translation Stats from Transifex
 *
 * @since 1.0
 *
 * @param string $slug Transifex slug
 */
function codepress_the_transifex_stats( $project, $resource = '' ) {

	// Trigger scripts, these will be place in the footer.
	Codepress_Transifex::scripts();

	?>
	<div class='transifex-stats' data-project-slug='<?php echo $project; ?>' data-resource-slug='<?php echo $resource; ?>'></div>
	<?php
}