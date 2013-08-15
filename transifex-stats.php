<?php

/*
Plugin Name: 	Transifex Stats
Version: 		1.0
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
define( 'CPTI_TEXTDOMAIN', 	'transifex-stats' );
define( 'CPTI_SLUG', 		'transifex-stats' );
define( 'CPTI_URL', 		plugin_dir_url( __FILE__ ) );
define( 'CPTI_DIR', 		plugin_dir_path( __FILE__ ) );

// Dependencies
require 'classes/class-transifex-api.php';
require 'classes/class-admin.php';
require 'classes/class-functions.php';
require 'classes/class-shortcode.php';

// Enables automatic plugin updates
include_once 'classes/class-update.php';
new CAC_Addon_Update( array(
	'store_url'			=> 'http://www.codepresshq.com',
	'product_id'		=> 'transifex-stats',
	'version'			=> CPTI_VERSION,
	'secret_key'		=> 'jhdsh23489hsdfkja9HHe',
	'product_name'		=> 'Transifex Stats',
	'file'				=> __FILE__
));

// @todo
// require 'classes/class-widget.php';

/**
 * Class Codepress_Transifex
 *
 * @since
 */
class Codepress_Transifex {

	function __construct() {

		load_plugin_textdomain( CPTI_TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

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
			'ajaxurl' 	=> admin_url('admin-ajax.php'),
			'no_result' => __( 'No results', CPTI_TEXTDOMAIN )
		));
	}

	/**
	 * Get project resources
	 *
	 * @since 1.0
	 *
	 * @param string $project Transifex project slug
	 * @return array API result
	 */
	function get_project( $project ) {

		$api = new Codepress_Transifex_API();
		return $api->connect_api( "project/{$project}?details" );
	}

	/**
	 * Getlanguage
	 *
	 * @since 1.0
	 *
	 * @param string $language_code Transifex language code
	 * @return array API result
	 */
	function get_language( $language_code ) {

		$api = new Codepress_Transifex_API();
		return $api->connect_api( "language/{$language_code}" );
	}

	/**
	 * Handle AJAX
	 *
	 * @since 1.0
	 */
	function ajax_get_project_stats() {

		$project 	= isset( $_POST['project_slug'] ) 	? $_POST['project_slug'] 	: '';
		$resource 	= isset( $_POST['resource_slug'] ) 	? $_POST['resource_slug'] 	: '';

		$this->display_stats( $project, $resource );

		exit;
	}

	/**
	 * Sort object by property
	 *
	 * @since 1.0
	 */
	function sort_objects_by_completion( $b, $a ) {
		if ( (int) $a->completed == (int) $b->completed ) return 0 ;
		return ( (int) $a->completed < (int) $b->completed) ? -1 : 1;
	}

	/**
	 * Is error
	 *
	 * @since 1.0
	 *
	 * @param string $response API response
	 * @return bool Error
	 */
	function maybe_display_error( $response ) {

		$error = '';

		if ( ! $response )
			$error = __('No results', CPTI_TEXTDOMAIN );

		if ( is_array( $response ) && isset( $response['error'] ) )
			$error = $response['error']['message'] . ' (' . $response['error']['code'] . ')';

		if( ! $error ) return false;

		echo $error;
		return true;
	}

	/**
	 * Display stats
	 *
	 * @since 1.0
	 *
	 * @param string $project_slug Transifex Project slug
	 * @param string $resource_slug Transifex Resource slug
	 */
	function display_stats( $project_slug = '', $resource_slug = '' ) {

		if ( ! $project_slug )
			return;

		$project = $this->get_project( $project_slug );

		// is error?
		if ( $this->maybe_display_error( $project ) ) return;

		// get first resource from project if left empty
		if ( ! $resource_slug ) {
			if ( empty( $project->resources ) )
				return;

			$resource_slug = $project->resources[0]->slug;
		}

		// connect to API
		$api 	= new Codepress_Transifex_API();
		$stats 	= $api->connect_api( "project/{$project_slug}/resource/{$resource_slug}/stats/" );

		// is error?
		if ( $this->maybe_display_error( $stats ) ) return;

		// sort stats by completion
		$stats = (array) $stats;
		uasort( $stats, array( $this, 'sort_objects_by_completion' ) );

		?>

	<?php if ( $project_title = apply_filters( 'cpti_project_title', $project->name ) ) : ?>
		<div class="transifex-title"><?php echo $project_title; ?></div>
	<?php endif; ?>
		<ul>
			<?php foreach ( $stats as $language_code => $resource ) : ?>
				<?php $language = $this->get_language( $language_code ); ?>
			<li class="clearfix">
				<div class="language_name">
					<?php echo $language->name; ?>
				</div>
				<div class="statbar">
					<div class="graph_resource">
						<div class="translated_comp" style="width:<?php echo $resource->completed; ?>;"></div>
					</div>
					<div class="stats_string_resource">
						<?php echo $resource->completed; ?>
					</div>
					<div class="go_translate">
						<a target="_blank" href="https://www.transifex.com/projects/p/<?php echo $project_slug; ?>/language/<?php echo $language_code; ?>/"><?php _e( 'Translate', CPTI_TEXTDOMAIN ); ?></a>
					</div>
				</div>
			</li>
			<?php  endforeach; ?>
		</ul>

		<?php
	}
}

new Codepress_Transifex();



