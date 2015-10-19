<?php

// Admin UI
class Codepress_Transifex_Admin {

	private $notices = array();

	function __construct() {
		add_action( 'admin_menu', array( $this, 'settings_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links',  array( $this, 'add_settings_link' ), 1, 2 );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	public function settings_menu() {

		$page = add_options_page( __( 'Transifex Stats', 'transifex-stats' ), __( 'Transifex Stats', 'transifex-stats' ), 'manage_options', CPTI_SLUG, array( $this, 'plugin_settings_page') );
		add_action( "admin_print_styles-{$page}", array( $this, 'admin_styles') );

		// verify credentials
		add_action( "load-{$page}", array( $this, 'verify_credentials' ) );
	}

	public function verify_credentials() {

		if ( false !== get_option('cptw_options') && isset( $_REQUEST['settings-updated'] ) && 'true' == $_REQUEST['settings-updated'] ) {

			$api = new Codepress_Transifex_API();
			$result = $api->verify_credentials();

			if ( ! $result ) {
				$this->notices[] = (object) array(
					'message' 	=> __( 'Your transifex credentials are incorrect.', 'transifex-stats' ),
					'class'		=> 'error'
				);
			}
		}
	}

	public function admin_styles() {
		wp_enqueue_style( 'cpti-admin', CPTI_URL.'/assets/css/admin_settings.css', array(), CPTI_VERSION, 'all' );
	}

	public function add_settings_link( $links, $file ) {
		if ( $file != CPTI_SLUG . '/' . CPTI_SLUG . '.php' ) {
			return $links;
		}
		array_unshift( $links, '<a href="' .  admin_url("admin.php") . '?page=' . CPTI_SLUG . '">' . __( 'Settings', 'transifex-stats' ) . '</a>' );
		return $links;
	}

	public function sanitize_options( $options ) {
		$options = array_map( 'sanitize_text_field', $options );
		$options = array_map( 'trim', $options );
		return $options;
	}

	public function register_settings() {
		if ( false === get_option( 'cpti_options' ) ) {
			add_option( 'cpti_options', $this->get_default_values() );
		}
		register_setting( 'cpti-settings-group', 'cpti_options', array( $this, 'sanitize_options' ) );
	}

	public function get_default_values() {
		$defaults = array(
			'username'	=> '',
			'password' 	=> '',
		);
		return apply_filters( 'cpti-defaults', $defaults );
	}

	public function admin_notices() {
		if ( ! $this->notices ) {
			return;
		}
		foreach ( $this->notices as $notice ) { ?>
		    <div class="<?php echo $notice->class; ?>">
		        <p><?php echo $notice->message; ?></p>
		    </div>
		    <?php
		}
	}

	public function plugin_settings_page() {

		$options = get_option( 'cpti_options' );
		?>
		<div id="cpti" class="wrap">
			<?php screen_icon( CPTI_SLUG ); ?>
			<h2><?php _e('Transifex Stats Settings', 'transifex-stats'); ?></h2>

			<form method="post" action="options.php">

				<?php settings_fields( 'cpti-settings-group' ); ?>

				<table class="form-table">
					<tbody>

						<tr valign="top">
							<th scope="row" colspan="2">
								<p><?php _e( 'Fill in your Transifex credentials below to make a connection with the Transifex API.', 'transifex-stats' ); ?></p>
								<p><?php _e( 'Your credentials will remain private and will only be used to connect with Transifex API.', 'transifex-stats' ); ?></p>
							</th>
						</tr>

						<tr valign="top">
							<th scope="row">
								<label for="username"><?php _e( 'Username', 'transifex-stats' ) ?></label>
							</th>
							<td>
								<label for="username">
									<input type="text" class="regular-text code" id="username" name="cpti_options[username]" value="<?php echo $options['username']; ?>">
								</label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label for="password"><?php _e( 'Password', 'transifex-stats' ) ?></label>
							</th>
							<td>
								<label for="password">
									<input type="password" class="regular-text code" id="password" name="cpti_options[password]" value="<?php echo $options['password']; ?>">
								</label>
							</td>
						</tr>

					</tbody>
				</table>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes'); ?>" />
				</p>
			</form>
			<p>
				<?php printf( __('This plugin is made by %s', 'transifex-stats' ), '<a target="_blank" href="http://www.codepresshq.com">Codepresshq.com</a>' ); ?>
			</p>
		</div>
	<?php
	}
}

new Codepress_Transifex_Admin();