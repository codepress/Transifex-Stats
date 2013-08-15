<?php

// @dev_only:
set_site_transient( 'update_plugins', null );

if( ! class_exists( 'CAC_Addon_Update' ) ) {

	/**
	 * Addon update class
	 *
	 * Example usage:
	 * new CAC_Addon_Update( array(
	 *		'store_url'			=> 'http://www.codepresshq.com',
	 *		'product_id'		=> 'cac-addon-name',
	 *		'product_name'		=> 'Addon Name',
	 *		'version'			=> '1.0',
	 *		'secret_key'		=> 'randomstring',
	 *		'file'				=> __FILE__
	 * ));
	 *
	 * @version 1.0
	 * @since 1.0
	 */
	class CAC_Addon_Update {

		/**
		 * The URL of the site with EDD installed
		 *
		 * @since 1.0
		 */
		private $store_url;

		/**
		 * The ID of the product.
		 *
		 * This needs to match the download name in EDD exactly
		 *
		 * @since 1.0
		 */
		private $product_id;

		/**
		 * Product version
		 *
		 * @since 1.0
		 */
		private $version;

		/**
		 * Slug
		 *
		 * @since 1.0
		 */
		private $file;

		/**
		 * Secret Key
		 *
		 * @since 1.0
		 */
		private $secret_key;

		/**
		 * Product Name
		 *
		 * @since 1.0
		 */
		private $product_name;

		/**
		 * Option prefix
		 *
		 * @since 1.0
		 */
		private $option_prefix;

		/**
		 * Construct
		 *
		 * @since 1.0
		 *
		 * @param array $args Arguments; This must contain: store_url, product_id, version, file, secret_key, product_name
		 */
		function __construct( $args ) {

			extract( $args );

			$this->store_url 		= $store_url;
			$this->product_id 		= $product_id;
			$this->version			= $version;
			$this->file 			= $file;
			$this->secret_key 		= $secret_key;
			$this->product_name		= $product_name;

			$this->option_prefix	= 'cpupdate_';

			// Add UI
			add_filter( 'cac/settings/groups', array( $this, 'settings_group' ) );
			add_action( 'cac/settings/groups/row=addons', array( $this, 'display' ) );

			// licence Requests
			add_action( 'admin_init', array( $this, 'handle_request' ) );

			// Activate licence on plugin install
			add_action( 'admin_init', array( $this, 'auto_activate_licence' ) );

			// Hook into WP update process
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'pre_set_site_transient_update_plugins_filter' ) );
			add_filter( 'plugins_api', array( $this, 'display_plugin_details' ), 10, 3 );
		}

		/**
		 * Get licence key
		 *
		 * You can add your licence key to your theme's functions.php by adding the following code:
		 * add_filter( 'cac_sc_licence_key', 'my_licence_key'); function my_licence_key() { return 'enter your licence key here'; }
		 *
		 * @since 1.0
		 */
		public function get_licence_key() {

			return trim( apply_filters( $this->product_id, get_option( $this->option_prefix . $this->product_id ) ) );
		}

		/**
		 * Get licence status
		 *
		 * @since 1.0
		 */
		function get_licence_status() {

			return get_option( $this->option_prefix . $this->product_id . '_sts' ) ? true : false;
		}

		/**
		 * Connect Official Software API
		 *
		 * @since 1.0
		 *
		 * Source: http://docs.woothemes.com/document/software-add-on/
		 *
		 * @param array $args
		 * @return string licence status
		 */
		function connect_api( $args ) {

			$query = add_query_arg( array_merge( array(
				'wc-api' 		=> 'software-api',
				'secret_key'	=> $this->secret_key,
				'product_id'	=> $this->product_id,
				'email'			=> ''
			), $args ), $this->store_url );

			// Call the custom API.
			$response = wp_remote_get( $query, array( 'timeout' => 15, 'sslverify' => false ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {

				cpac_admin_message( __( 'Could not connect to API. Try again at a later time please.', 'cpac' ) . ' (' . $response->get_error_message() . ')', 'error' );
				return false;
			}

			$result = json_decode( wp_remote_retrieve_body( $response ) );

			// error?
			if ( empty( $result ) ) {
				cpac_admin_message( __( 'Wrong response from API.', 'cpac' ), 'error' );
				return false;
			}

			return $result;
		}

		/**
		 * Handle requests for license activation
		 *
		 * @since 1.0
		 */
		public function handle_request() {

			// Activation
			if ( isset( $_POST['_wpnonce_addon_activate'] ) && wp_verify_nonce( $_POST['_wpnonce_addon_activate'], $this->product_id ) ) {

				$licence = isset( $_POST[ $this->product_id ] ) ? sanitize_text_field( $_POST[ $this->product_id ] ) : '';

				$this->activate_licence( $licence );
			}

			// Deactivation
			if ( isset( $_POST['_wpnonce_addon_deactivate'] ) && wp_verify_nonce( $_POST['_wpnonce_addon_deactivate'], $this->product_id ) ) {

				$this->deactivate_licence();
			}
		}

		/**
		 * Activate licence
		 *
		 * @since 1.0
		 */
		public function activate_licence( $licence_key, $email = '', $show_message = true ) {

			// update licence
			delete_option( $this->option_prefix . $this->product_id );
			delete_option( $this->option_prefix . $this->product_id . '_sts' );

			// licence empty?
			if ( ! $licence_key ) {
				if ( $show_message ) cpac_admin_message( $this->product_name . ' ' . __( 'licence is empty.', 'cpac' ), 'error' );
				return;
			}

			// get licence status
			$status = $this->connect_api( array(
				'request' 		=> 'activation',
				'email'			=> $email,
			    'licence_key'	=> $licence_key,
			    'instance'		=> site_url() // identifying
			));

			// API down?
			if ( ! $status ) return false;

			// error?
			if ( isset( $status->error ) ) {
				$additional_info = isset( $status->{"additional info"} ) ? " " . $status->{"additional info"} : '';
				if ( $show_message ) cpac_admin_message( $this->product_name . ' ' . __('error') . ': ' . "<strong>{$status->error}</strong>." . $additional_info, 'error' );
				if ( $show_message && '103' == $status->code ) cpac_admin_message( sprintf( __( 'Login into your account at %s to see your current licence activations.', 'cpac' ), '<a href="http://codepresshq.com/my-account/">codepresshq.com</a>' ), 'error' ); // limit exceeded
				return false;
			}
			if ( ! isset( $status->activated ) || true !== $status->activated ) {
				if ( $show_message ) cpac_admin_message( $this->product_name . ' ' . __('activation failed', 'cpac' ), 'error' );
				return false;
			}

			// succes?
			if ( $show_message ) {
				$status->message = false !== strpos( $status->message, '999999' ) ? '' : "({$status->message})"; // unlimited activations remaining
				cpac_admin_message( $this->product_name . ' ' . __( 'licence is <strong>activated</strong>.', 'cpac' ) . ' ' . $status->message, 'updated' );
			}

			// update status
			update_option( $this->option_prefix . $this->product_id, $licence_key );
			update_option( $this->option_prefix . $this->product_id . '_sts', true );

			return true;
		}

		/**
		 * Deactivate licence
		 *
		 * @since 1.0
		 */
		public function deactivate_licence() {

			$licence_key 	= $this->get_licence_key();
			$licence_status = $this->get_licence_status();

			if ( ! $licence_key ) {
				cpac_admin_message( __( 'No licence key found.', 'cpac' ), 'error' );
				return false;
			}

			if ( ! $licence_status ) {
				cpac_admin_message( __( 'Licence key is not active.', 'cpac' ), 'error' );
				return false;
			}

			// get licence status
			$status = $this->connect_api( array(
				'request' 		=> 'deactivation',
			    'licence_key'	=> $licence_key,
			    'instance'		=> site_url() // identifying
			));

			// API down?
			if ( ! $status ) return false;

			// Error; invalid instance, no matching licence etc.
			if ( isset( $status->error ) ) {
				cpac_admin_message( $status->error , 'error' );
			}

			// remove licence
			delete_option( $this->option_prefix . $this->product_id );
			delete_option( $this->option_prefix . $this->product_id . '_sts' );

			cpac_admin_message( $this->product_name . ' ' . __( 'licence is <strong>deactivated</strong>.', 'cpac' ), 'updated' );
		}

		/**
		 * Activate licence on install
		 *
		 * @since 1.0
		 */
		public function auto_activate_licence() {

			// already valid licence present?
			if ( $this->get_licence_status() ) return;

			$licence = $this->get_licence_key();
			if ( ! $licence || get_transient( $licence ) ) return;

			// activate
			$this->activate_licence( $licence, '', false );
			set_transient( $licence, 1 );
		}

		/**
		 * Add settings group to Admin Columns settings page
		 *
		 * @since 1.0
		 */
		public function settings_group( $groups ) {

			if ( isset( $groups['addons'] ) )
				return $groups;

			$groups['addons'] =  array(
				'title'			=> __( 'Add-ons updates', 'cpac' ),
				'description'	=> __( 'Enter your licence to receive automatic updates.', 'cpac' )
			);

			return $groups;
		}

		/**
		 * Display licence field
		 *
		 * @since 1.0
		 */
		function display() {

			// Use this hook when you want to hide to licene form
			if ( ! apply_filters( 'cac/display_licence/addon=' . $this->product_id , true ) ) return;

			$licence = $this->get_licence_key();
			$status  = $this->get_licence_status();

			// on submit
			if ( ! empty( $_POST[ $this->product_id ] ) )
				$licence = $_POST[ $this->product_id ];

			?>

			<form action="" method="post">
				<label for="<?php echo $this->product_id; ?>">
					<strong><?php echo $this->product_name; ?></strong>
				</label>
				<br/>

			<?php if ( $status ) : ?>

				<?php wp_nonce_field( $this->product_id, '_wpnonce_addon_deactivate' ); ?>

				<span class="icon-yes"></span>
				<?php _e( 'Automatic updates are enabled.', 'cpac' ); ?>
				<input type="submit" class="button" value="<?php _e( 'Deactivate licence', 'cpac' ); ?>" >

			<?php else : ?>

				<?php wp_nonce_field( $this->product_id, '_wpnonce_addon_activate' ); ?>

				<input type="password" value="<?php echo $licence; ?>" id="<?php echo $this->product_id; ?>" name="<?php echo $this->product_id; ?>" size="30" placeholder="<?php _e( 'Fill in your licence code', 'cpac' ) ?>" >
				<input type="submit" class="button" value="<?php _e( 'Update licence', 'cpac' ); ?>" >
				<p class="description">
					<?php _e( 'Enter your licence to receive automatic updates.', 'cpac' ); ?>
				</p>

			<?php endif; ?>

			</form>
			<br/>

			<?php
		}

		/**
		 * Get remote plugin info.
		 *
		 * Uses seperate API 'codepress-wc-api' for getting the plugin details: such as version number, download link
		 *
		 * @since 1.0
		 */
		public function get_remote_plugin_info() {

			$args = array(
				'codepress-wc-api' 	=> 'plugin_updater',
				'secret_key'		=> $this->secret_key,
				'product_id'		=> $this->product_id,
				'licence_key'		=> $this->get_licence_key(),
				'slug'				=> plugin_basename( $this->file ),
				'instance'			=> site_url()
			);

			$result = wp_remote_get( add_query_arg( $args, $this->store_url ), array( 'timeout' => 15, 'sslverify' => false ) );

			// Call the custom API.
			$response = json_decode( wp_remote_retrieve_body( $result ) );

			if ( ! $response )
				return;

			if ( isset( $response->error ) )
				return;

			return $response;
		}

		/**
		 * Check for Updates at the defined API endpoint and modify the update array.
		 *
		 * This function dives into the update api just when Wordpress creates its update array,
		 * then adds a custom API call and injects the custom plugin data retrieved from the API.
		 * It is reassembled from parts of the native Wordpress plugin update code.
		 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
		 *
		 * @uses api_request()
		 *
		 * @param array $_transient_data Update array build by Wordpress.
		 * @return array Modified update array with custom plugin data.
		 */
		function pre_set_site_transient_update_plugins_filter( $_transient_data ) {

			if( empty( $_transient_data ) )
				return $_transient_data;

			$plugin_info = $this->get_remote_plugin_info();

			if( false !== $plugin_info && is_object( $plugin_info ) ) {
				if( version_compare( $this->version, $plugin_info->new_version, '<' ) ) {

					// transient name can only be 45 characters long
					$slug = substr( $plugin_info->slug ,0 ,45 );

					$_transient_data->response[ $slug ] = $plugin_info;
				}
			}

			return $_transient_data;
		}

		/**
		 * Updates information on the "View version x.x details" page with custom data.
		 *
		 * @uses api_request()
		 *
		 * @param mixed $_data
		 * @param string $_action
		 * @param object $_args
		 * @return object $_data
		 */
		function display_plugin_details( $false, $action, $args ) {

			if ( 'plugin_information' !== $action )
				return $false;

			$plugin_info = $this->get_remote_plugin_info();

			if( ! $plugin_info || !isset( $args->slug ) || $args->slug != $plugin_info->slug )
				return $false;

			$plugin_info->sections = maybe_unserialize( $plugin_info->sections );

			return (object) $plugin_info;
		}
	}
}