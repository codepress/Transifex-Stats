<?php

/*
Plugin Name: 	Transifex Stats
Version: 		1.1
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
define( 'CPTI_SLUG', 		'transifex-stats' );
define( 'CPTI_URL', 		plugin_dir_url( __FILE__ ) );
define( 'CPTI_DIR', 		plugin_dir_path( __FILE__ ) );

// Dependencies
require 'classes/class-transifex-api.php';
require 'classes/class-transifex-stats.php';
require 'classes/class-admin.php';
require 'classes/class-functions.php';
require 'classes/class-shortcode.php';

/**
 * Class Codepress_Transifex
 *
 * @since 1.0
 */
class Codepress_Transifex {

	function __construct() {

		load_plugin_textdomain( 'transifex-stats', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		add_action( 'wp_ajax_transifex_project_stats', array( $this, 'ajax_project_translations' ) );
		add_action( 'wp_ajax_nopriv_transifex_project_stats', array( $this, 'ajax_project_translations' ) );

		add_action( 'wp_ajax_transifex_contributor_stats', array( $this, 'ajax_contributor_stats' ) );
		add_action( 'wp_ajax_nopriv_transifex_contributor_stats', array( $this, 'ajax_contributor_stats' ) );
	}

	/**
	 * Scripts
	 *
	 * @since 1.0
	 */
	static function scripts() {

		wp_enqueue_style( 'cp-transifex-css', CPTI_URL . '/assets/css/transifex.css', '', CPTI_VERSION );
		wp_enqueue_script( 'cp-transifex-js', CPTI_URL . '/assets/js/transifex.js', array('jquery'), CPTI_VERSION, true );

		wp_localize_script( 'cp-transifex-js', 'cpti', array(
			'ajaxurl' 	=> admin_url('admin-ajax.php'),
			'no_result' => __( 'No results', 'transifex-stats' )
		));
	}

	/**
	 * Handle AJAX
	 *
	 * @since 1.0
	 */
	function ajax_project_translations() {

		$project  = isset( $_POST['project_slug'] ) 	? $_POST['project_slug'] 	: '';
		$resource = isset( $_POST['resource_slug'] ) 	? $_POST['resource_slug'] 	: '';

		$stats = new Codepress_Transifex_Stats( $project, $resource );
		$stats->display_translations_progress();

		exit;
	}

	/**
	 * Handle AJAX
	 *
	 * @since 1.0
	 */
	function ajax_contributor_stats() {

		$project = isset( $_POST['project_slug'] ) 	? $_POST['project_slug'] 	: '';

		$stats = new Codepress_Transifex_Stats( $project );
		$stats->display_contributors();

		exit;
	}
}

new Codepress_Transifex();