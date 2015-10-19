<?php

// Transifex Stats Shortcode

class Codepress_Transifex_Stats_Shortcode {

	function __construct() {

		add_shortcode( 'transifex_stats', array( $this, 'render_shortcode_transifex_stats' ) );
		add_shortcode( 'transifex_contributors', array( $this, 'render_shortcode_transifex_contributors' ) );

		add_action( 'init', array( $this, 'add_button_to_toolbar' ) );
		add_action( 'admin_head', array( $this, 'add_button_icon' ) );

	}

	public function render_shortcode_transifex_stats( $atts ) {

		extract( $atts );

		if ( empty( $project ) ) {
			return false;
		}
		if ( ! isset( $resource ) ) {
			$resource = '';
		}

		ob_start();
		transifex_display_translation_progress( $project, $resource );

		return ob_get_clean();
	}

	public function render_shortcode_transifex_contributors( $atts ) {
		extract( $atts );

		if ( empty( $project ) ) {
			return false;
		}

		ob_start();
		transifex_display_translators( $project );

		return ob_get_clean();
	}

	public function add_button_to_toolbar() {
		if ( ! current_user_can('edit_posts') || ! current_user_can('edit_pages') || get_user_option('rich_editing') !== 'true' ) {
			return;
		}
		add_filter( "mce_external_plugins", array( $this, 'register_tinymce_button' ) );
		add_filter( 'mce_buttons', array( $this, 'add_tinymce_button' ) );
	}

	public function register_tinymce_button( $plugin_array ) {
		$plugin_array['transifex_stats'] = CPTI_URL . 'assets/js/toolbar-shortcode-button.js';
		return $plugin_array;
	}

	public function add_tinymce_button( $buttons ) {
		array_push( $buttons, "|", "transifex_stats" );
		return $buttons;
	}

	public function add_button_icon() { ?>
		<style type="text/css">
		.wp_themeSkin .mceIcon.mce_transifex_stats, /* v3.8.1 */
		.mce-ico.mce-i-transifex_stats { /* v3.9 */
			background: transparent url('<?php echo CPTI_URL; ?>assets/images/shortcode_btn_icon.png') no-repeat center center;
		}
		</style>
		<?php
	}
}

new Codepress_Transifex_Stats_Shortcode();