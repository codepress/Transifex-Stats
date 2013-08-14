<?php


/**
 * Codepress Transifex Admin
 *
 * @since 0.1
 */
class Codepress_Transifex_Admin {

	/**
	 * Notices
	 *
	 * @since 0.1
	 */
	private $notices = array();

	/**
	 * Constructor
	 *
	 * @since 0.1
	 */
	function __construct() {

		// Admin UI
		add_action( 'admin_menu', array( $this, 'settings_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links',  array( $this, 'add_settings_link' ), 1, 2 );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Admin Menu.
	 *
	 * Create the admin menu link for the settings page.
	 *
	 * @since 0.1
	 */
	public function settings_menu() {

		// options page; title, menu title, capability, slug, callback
		$page = add_options_page( __( 'Transifex', CPTI_TEXTDOMAIN ), __( 'Transifex', CPTI_TEXTDOMAIN ), 'manage_options', CPTI_SLUG, array( $this, 'plugin_settings_page') );

		// settings page specific styles and scripts
		add_action( "admin_print_styles-{$page}", array( $this, 'admin_styles') );

		// verify credentials
		add_action( "load-{$page}", array( $this, 'verify_credentials' ) );
	}

	/**
	 * Verify Credentials on storing credentials
	 *
	 * @since 0.1
	 */
	function verify_credentials() {

		if ( false !== get_option('cptw_options') && isset( $_REQUEST['settings-updated'] ) && 'true' == $_REQUEST['settings-updated'] ) {

			$api = new Codepress_Transifex_API();
			$result = $api->verify_credentials();

			if ( ! $result ) {
				$this->notices[] = (object) array(
					'message' 	=> __( 'Your transifex credentials are incorrect.', CPTI_TEXTDOMAIN ),
					'class'		=> 'error'
				);
			}
		}
	}

	/**
	 * Register admin css
	 *
	 * @since 0.1
	 */
	public function admin_styles() {

		wp_enqueue_style( 'cpti-admin', CPTI_URL.'/assets/css/admin_settings.css', array(), CPTI_VERSION, 'all' );
	}

	/**
	 * Add Settings link to plugin page
	 *
	 * @since 0.1
	 */
	public function add_settings_link( $links, $file ) {

		if ( $file != plugin_basename( __FILE__ ) )
			return $links;

		array_unshift( $links, '<a href="' .  admin_url("admin.php") . '?page=' . CPTI_SLUG . '">' . __( 'Settings', CPTI_TEXTDOMAIN) . '</a>' );

		return $links;
	}

	/**
	 * Sanitize options
	 *
	 * @since 0.1
	 */
	public function sanitize_options( $options ) {

		$options = array_map( 'sanitize_text_field', $options );
		$options = array_map( 'trim', $options );

		return $options;
	}

	/**
	 * Register plugin options
	 *
	 * @since 0.1
	 */
	public function register_settings() {

		// If we have no options in the database, let's add them now.
		if ( false === get_option( 'cpti_options' ) ) {
			add_option( 'cpti_options', $this->get_default_values() );
		}

		register_setting( 'cpti-settings-group', 'cpti_options', array( $this, 'sanitize_options' ) );
	}

	/**
	 * Returns the default plugin options.
	 *
	 * @since 0.1
	 */
	public function get_default_values() {

		$defaults = array(
			'username'	=> '',
			'password' 	=> '',
		);

		return apply_filters( 'CPTI_defaults', $defaults );
	}

	/**
	 * Admin Notices
	 *
	 * @since 0.1
	 */
	function admin_notices() {

		if ( ! $this->notices )
			return;


		foreach ( $this->notices as $notice ) {
			?>
		    <div class="<?php echo $notice->class; ?>">
		        <p><?php echo $notice->message; ?></p>
		    </div>
		    <?php
		}
	}

	/**
	 * Settings Page Template.
	 *
	 * This function in conjunction with others usei the WordPress
	 * Settings API to create a settings page where users can adjust
	 * the behaviour of this plugin.
	 *
	 * @since 0.1
	 */
	public function plugin_settings_page() {

		$options = get_option( 'cpti_options' );

	?>
	<div id="cpti" class="wrap">
		<?php screen_icon( CPTI_SLUG ); ?>
		<h2><?php _e('Transifex Settings', CPTI_TEXTDOMAIN); ?></h2>

		<form method="post" action="options.php">

			<?php settings_fields( 'cpti-settings-group' ); ?>

			<table class="form-table">
				<tbody>

					<tr valign="top">
						<th scope="row" colspan="2">
							<p><?php _e( 'Fill in your Transifex credentials to make a connection with the API.', CPTI_TEXTDOMAIN ); ?></p>
						</th>
					</tr>

					<tr valign="top">
						<th scope="row">
							<label for="username"><?php _e( 'Username', CPTI_TEXTDOMAIN ) ?></label>
						</th>
						<td>
							<label for="username">
								<input type="text" class="regular-text code" id="username" name="cpti_options[username]" value="<?php echo $options['username']; ?>">
							</label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="password"><?php _e( 'Password', CPTI_TEXTDOMAIN ) ?></label>
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
	</div>
	<?php
	}
}

new Codepress_Transifex_Admin();

