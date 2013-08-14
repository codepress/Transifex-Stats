<?php

/**
 * Display Translation Stats from Transifex
 *
 * @since 1.0
 *
 * @param string $slug Transifex slug
 */
function codepress_the_transifex_stats( $project, $resource = '' ) {
	?>
	<div class='transifex-stats' data-project-slug='<?php echo $project; ?>' data-resource-slug='<?php echo $resource; ?>'></div>
	<?php
}