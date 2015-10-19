<?php

/**
 * Transifex API
 *
 * @since 1.0
 */
class Codepress_Transifex_API {

	private $api_url, $auth, $cache_time;

	public function __construct( $cache_time = 3600 ) {

		$this->api_url = 'https://www.transifex.com/api/2/';
		$this->cache_time = $cache_time;
		$this->set_credentials();
	}

	public function set_credentials() {

		$credentials = get_option( 'cpti_options' );

		$username = isset( $credentials['username'] ) ? $credentials['username'] : '';
		$password = isset( $credentials['password'] ) ? $credentials['password'] : '';

		if ( $username && $password ) {
			$this->auth = $username . ':' . $password;
		}
	}

	public function verify_credentials() {
		return true; // @todo: contact transifex how to verify credentials
	}

	public function is_api_error( $response ) {

		$error = false;
		if ( ! $response ) {
			$error = __( 'No response', 'transifex-stats' );
		}
		elseif ( is_wp_error( $response ) ) {
			$error = $response->get_error_message();
		}
		elseif ( isset( $response['body'] ) && is_string( $response['body'] ) ) {
			if ( 200 !== $response['response']['code'] ) {
				$error = $response['response']['message'];
			}
		}
		return $error;
	}

	public function is_ssl_error( $response ) {
		return is_wp_error( $response ) && ( false !== strstr( $response->get_error_message(), 'certificate verification is disabled' ) );
	}

	/**
	 * Connect API
	 *
	 * @since 1.0
	 *
	 * @param string $request API variable; e.g. projects
	 */
	public function connect_api( $request, $ssl_verify = false ) {

		$cache_id = md5( $request );
		$long_cache_id = md5( $request . 'long' );

		$result = get_transient( $cache_id );

		if ( ! $result && null !== $this->cache_time ) {
			$args = array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $this->auth )
				),
				'timeout' 	=> 120, // 2 min
				'sslverify' => apply_filters( 'transifex_connect_api_sslverify', $ssl_verify )
			);

			$response = wp_remote_get( $this->api_url . $request, $args );

			//if ( $this->is_ssl_error( $response ) ) {
				//$this->connect_api( $request, ! $ssl_verify );
			//}

			if ( $error = $this->is_api_error( $response ) ) {
				return $error;
			}

			if ( $json = wp_remote_retrieve_body( $response ) ) {
				$result = json_decode( $json );

				set_transient( $cache_id, $result, $this->cache_time ); // refresh cache x hours
				set_transient( $long_cache_id, $result ); // forever
			}
		}

		if ( ! $result ) {
			$result = get_transient( $long_cache_id );
		}

		// shit just hit the fan...
		if ( ! $result ) {
			return false;
		}
		return $result;
	}
}
