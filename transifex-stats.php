<?php

/*
Plugin Name: 	Transifex Stats
Version: 		0.1
Description: 	Display transifex translation progress
Author: 		Codepress
Author URI: 	http://www.codepresshq.com
Plugin URI: 	http://www.codepresshq.com/plugins
Text Domain: 	transifex-stats
Domain Path: 	/languages
License:		GPLv2

Copyright 2011-2013  Codepress  info@codepress.nl

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License version 2 as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'CPTI_VERSION', 	'1.0' );
define( 'CPTI_URL', 		plugins_url( '', __FILE__ ) );
define( 'CPTI_TEXTDOMAIN', 	'transifex-stats' );
define( 'CPTI_SLUG', 		'codepress-transifex' );

load_plugin_textdomain( CPTI_TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

require 'classes/class-transifex-api.php';
require 'classes/class-admin.php';

/**
 * Class Codepress_Transifex
 *
 * @since
 */
class Codepress_Transifex {

	function __construct() {

		add_action( 'wp_enqueue_scripts' , array( $this, 'scripts') );

		add_action( 'wp_ajax_transifex_project_stats', array( $this, 'ajax_get_project_stats' ) );
		add_action( 'wp_ajax_nopriv_transifex_project_stats', array( $this, 'ajax_get_project_stats' ) );
	}

	/**
	 * Scripts
	 *
	 * @since 1.0
	 */
	function scripts() {

		wp_enqueue_style( 'cp-transifex-css', CPTI_URL . '/assets/css/transifex.css', '', CPTI_VERSION );
		wp_enqueue_script( 'cp-transifex-js', CPTI_URL . '/assets/js/transifex.js', array('jquery'), CPTI_VERSION, true );

		wp_localize_script( 'cp-transifex-js', 'cpti', array(
			'ajaxurl' => admin_url('admin-ajax.php')
		));
	}

	/**
	 * Get project resources
	 *
	 * @since 1.0
	 */
	function get_project( $project ) {

		$api = new Codepress_Transifex_API();
		return $api->connect_api( "project/{$project}?details" );
	}

	/**
	 * Getlanguage
	 *
	 * @since 1.0
	 */
	function get_language( $language_code ) {

		$api = new Codepress_Transifex_API();
		return $api->connect_api( "language/{$language_code}" );
	}

	/**
	 * Handle AJAX
	 *
	 * @since 1.0
	 *
	 * @param string $project Transifex project slug
	 * @param string $resource Transifex resrouce slug (optionel )
	 */
	function ajax_get_project_stats() {

		$project 	= isset( $_POST['project_slug'] ) ? $_POST['project_slug'] : '';
		$resource 	= isset( $_POST['resource_slug'] ) ? $_POST['resource_slug'] : '';

		$this->display_stats( $project, $resource );

		exit;
	}

	/**
	 * Sort object by property
	 *
	 * @since 1.0
	 */
	function sort_objects_by_completion( $b, $a ) {
		if( (int) $a->completed == (int) $b->completed ) return 0 ;
		return ( (int) $a->completed < (int) $b->completed) ? -1 : 1;
	}

	/**
	 * Display stats
	 *
	 * @since 1.0
	 */
	function display_stats( $project_slug = '', $resource_slug = '' ) {

		if ( ! $project_slug )
			return;

		// get first resource from project if left empty
		if ( ! $resource_slug ) {
			$project = $this->get_project( $project_slug );

			if ( empty( $project->resources ) )
				return;

			$resource_slug = $project->resources[0]->slug;
		}

		// connect to API
		$api = new Codepress_Transifex_API();
		$stats = $api->connect_api( "project/{$project_slug}/resource/{$resource_slug}/stats/" );

		if ( ! $stats ) return;

		// sort stats by completion
		$stats = (array) $stats;
		uasort( $stats, array( $this, 'sort_objects_by_completion' ) );

		?>
		<table class="codepress-transifex">
			<tbody>
				<?php foreach ( $stats as $language_code => $resource ) : ?>
					<?php $language = $this->get_language( $language_code ); ?>
				<tr>
					<td>
						<div class="language_name">
							<?php echo $language->name; ?>
						</div>
					</td>
					<td>
						<div class="statbar" >
							<div class="graph_resource">
								<div class="translated_comp" style="width:<?php echo $resource->completed; ?>;"></div>
							</div>
							<div class="stats_string_resource">
								<?php echo $resource->completed; ?>
							</div>
							<?php if ( $project_slug ) : ?>
							<div class="go_translate">
								<a target="_blank" href="https://www.transifex.com/projects/p/<?php echo $project_slug; ?>/language/<?php echo $language_code; ?>/"><?php _e( 'Translate', CPTI_TEXTDOMAIN ); ?></a>
							</div>
							<?php endif; ?>
						</div>
					</td>
				</tr>
				<?php  endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}

new Codepress_Transifex();



