<?php

defined( 'JETPACK__VERSION' ) or define( 'JETPACK__VERSION', '7.2-alpha' );
defined( 'JETPACK__API_VERSION' ) or define( 'JETPACK__API_VERSION', 1 );
defined( 'JETPACK_MASTER_USER' ) or define( 'JETPACK_MASTER_USER', true );
defined( 'JETPACK__API_BASE' ) or define( 'JETPACK__API_BASE', 'https://jetpack.wordpress.com/jetpack.' );

if ( class_exists( 'WC_Connect_Jetpack' ) ) {
	return;
}

class Jetpack_Dino {
	static $instance = false;

	public static function init() {
		if ( ! self::$instance ) {
			self::$instance = new Jetpack_Dino;
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	function rest_api_init() {
		register_rest_route( 'jetpack/v4', '/verify_registration', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => __CLASS__ . '::verify_registration',
		) );

		register_rest_route( 'jetpack/v4', '/remote_authorize', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => __CLASS__ . '::remote_authorize',
		) );
	}

	function admin_menu() {
		add_submenu_page( null, 'Jetpack', 'Jetpack', 'install_plugins', 'jetpack', array( $this, 'admin_page_load' ) );
	}

	public static function remote_authorize( $request ) {
		$user = get_user_by( 'id', $request['state'] );
		//JetpackTracking::record_user_event( 'jpc_remote_authorize_begin', array(), $user );
		foreach( array( 'secret', 'state', 'redirect_uri', 'code' ) as $required ) {
			if ( ! isset( $request[ $required ] ) || empty( $request[ $required ] ) ) {
				return new WP_Error( 'test_error' ); // $this->error( new Jetpack_Error( 'missing_parameter', 'One or more parameters is missing from the request.', 400 ), 'jpc_remote_authorize_fail' );
			}
		}
		if ( ! $user ) {
			return new WP_Error( 'test_error' ); // $this->error( new Jetpack_Error( 'user_unknown', 'User not found.', 404 ), 'jpc_remote_authorize_fail' );
		}
		if ( Jetpack::is_active() && Jetpack::is_user_connected( $request['state'] ) ) {
			return new WP_Error( 'test_error' ); // $this->error( new Jetpack_Error( 'already_connected', 'User already connected.', 400 ), 'jpc_remote_authorize_fail' );
		}
		$verified = self::verify_action( array( 'authorize', $request['secret'], $request['state'] ) );
		if ( is_a( $verified, 'IXR_Error' ) ) {
			return new WP_Error( 'test_error' ); // $this->error( $verified, 'jpc_remote_authorize_fail' );
		}
		wp_set_current_user( $request['state'] );
		$client_server = new Jetpack_Client_Server;

		$current_user_id = get_current_user_id();
		$token = $this->get_token( $data );
		$is_master_user = true;
		self::update_user_token( $current_user_id, sprintf( '%s.%d', $token, $current_user_id ), $is_master_user );
		$result = 'authorized'; //$client_server->authorize( $request );
		// if ( is_wp_error( $result ) ) {
		// 	return $this->error( $result, 'jpc_remote_authorize_fail' );
		// }
		// JetpackTracking::record_user_event( 'jpc_remote_authorize_success' );
		return array(
			'result' => $result,
		);
	}

	// https://github.com/Automattic/jetpack/blob/50b583b8ee7366dd958f8611c5d9407c4a2290a3/class.jetpack-xmlrpc-server.php#L402
	public static function verify_registration( $request ) {
		return self::verify_action( array( 'register', $request['secret_1'], $request['state'] ) )
	}

	// https://github.com/Automattic/jetpack/blob/50b583b8ee7366dd958f8611c5d9407c4a2290a3/class.jetpack-xmlrpc-server.php#L428
	public static function verify_action( $params ) {
		$action = $params[0];
		$verify_secret = $params[1];
		$state = isset( $params[2] ) ? $params[2] : '';
		$user = get_user_by( 'id', $state );
		//JetpackTracking::record_user_event( 'jpc_verify_' . $action . '_begin', array(), $user );
		$tracks_failure_event_name = 'jpc_verify_' . $action . '_fail';
		if ( empty( $verify_secret ) ) {
			return new WP_Error( 'test_error' ); // $this->error( new Jetpack_Error( 'verify_secret_1_missing', sprintf( 'The required "%s" parameter is missing.', 'secret_1' ), 400 ), $tracks_failure_event_name, $user );
		} else if ( ! is_string( $verify_secret ) ) {
			return new WP_Error( 'test_error' ); // $this->error( new Jetpack_Error( 'verify_secret_1_malformed', sprintf( 'The required "%s" parameter is malformed.', 'secret_1' ), 400 ), $tracks_failure_event_name, $user );
		} else if ( empty( $state ) ) {
			return new WP_Error( 'test_error' ); // $this->error( new Jetpack_Error( 'state_missing', sprintf( 'The required "%s" parameter is missing.', 'state' ), 400 ), $tracks_failure_event_name, $user );
		} else if ( ! ctype_digit( $state ) ) {
			return new WP_Error( 'test_error' ); // $this->error( new Jetpack_Error( 'state_malformed', sprintf( 'The required "%s" parameter is malformed.', 'state' ), 400 ), $tracks_failure_event_name, $user );
		}
		$secrets = self::get_secrets( $action, $state );
		if ( ! $secrets ) {
			self::delete_secrets( $action, $state );
			return new WP_Error( 'test_error' ); // $this->error( new Jetpack_Error( 'verify_secrets_missing', 'Verification secrets not found', 400 ), $tracks_failure_event_name, $user );
		}
		if ( is_wp_error( $secrets ) ) {
			self::delete_secrets( $action, $state );
			return new WP_Error( 'test_error' ); // $this->error( new Jetpack_Error( $secrets->get_error_code(), $secrets->get_error_message(), 400 ), $tracks_failure_event_name, $user );
		}
		if ( empty( $secrets['secret_1'] ) || empty( $secrets['secret_2'] ) || empty( $secrets['exp'] ) ) {
			self::delete_secrets( $action, $state );
			return new WP_Error( 'test_error' ); // $this->error( new Jetpack_Error( 'verify_secrets_incomplete', 'Verification secrets are incomplete', 400 ), $tracks_failure_event_name, $user );
		}
		if ( ! hash_equals( $verify_secret, $secrets['secret_1'] ) ) {
			self::delete_secrets( $action, $state );
			return new WP_Error( 'test_error' ); // $this->error( new Jetpack_Error( 'verify_secrets_mismatch', 'Secret mismatch', 400 ), $tracks_failure_event_name, $user );
		}
		self::delete_secrets( $action, $state );
		//JetpackTracking::record_user_event( 'jpc_verify_' . $action . '_success', array(), $user );
		return $secrets['secret_2'];
	}

	function admin_page_load() {
		// https://github.com/Automattic/jetpack/blob/50b583b8ee7366dd958f8611c5d9407c4a2290a3/class.jetpack.php#L4179
		if ( isset( $_GET['action'] ) && 'register' === $_GET['action'] ) {
			check_admin_referer( 'jetpack-register' );
			// Jetpack::log( 'register' );
			// Jetpack::maybe_set_version_option();
			$registered = self::try_registration();
			if ( is_wp_error( $registered ) ) {
				$error = $registered->get_error_code();
				// Jetpack::state( 'error', $error );
				// Jetpack::state( 'error', $registered->get_error_message() );
				// JetpackTracking::record_user_event( 'jpc_register_fail', array(
				// 	'error_code' => $error,
				// 	'error_message' => $registered->get_error_message()
				// ) );
				return;
			}
			$from = isset( $_GET['from'] ) ? $_GET['from'] : false;
			$redirect = isset( $_GET['redirect'] ) ? $_GET['redirect'] : false;
			// JetpackTracking::record_user_event( 'jpc_register_success', array(
			// 	'from' => $from
			// ) );
			$url = self::build_connect_url( true, $redirect, $from );
			if ( ! empty( $_GET['onboarding'] ) ) {
				$url = add_query_arg( 'onboarding', $_GET['onboarding'], $url );
			}
			if ( ! empty( $_GET['auth_approved'] ) && 'true' === $_GET['auth_approved'] ) {
				$url = add_query_arg( 'auth_approved', 'true', $url );
			}
			wp_redirect( $url );
			exit;
		}
	}

	//https://github.com/Automattic/jetpack/blob/50b583b8ee7366dd958f8611c5d9407c4a2290a3/class.jetpack.php#L3486
	public static function try_registration() {
		// The user has agreed to the TOS at some point by now.
		// Jetpack_Options::update_option( 'tos_agreed', true );
		// Let's get some testing in beta versions and such.
		// if ( self::is_development_version() && defined( 'PHP_URL_HOST' ) ) {
		// 	// Before attempting to connect, let's make sure that the domains are viable.
		// 	$domains_to_check = array_unique( array(
		// 		'siteurl' => parse_url( get_site_url(), PHP_URL_HOST ),
		// 		'homeurl' => parse_url( get_home_url(), PHP_URL_HOST ),
		// 	) );
		// 	foreach ( $domains_to_check as $domain ) {
		// 		$result = Jetpack_Data::is_usable_domain( $domain );
		// 		if ( is_wp_error( $result ) ) {
		// 			return $result;
		// 		}
		// 	}
		// }
		$result = self::register();
		// If there was an error with registration and the site was not registered, record this so we can show a message.
		if ( ! $result || is_wp_error( $result ) ) {
			return $result;
		} else {
			return true;
		}
	}

	// https://github.com/Automattic/jetpack/blob/50b583b8ee7366dd958f8611c5d9407c4a2290a3/class.jetpack.php#L5458
	public static function register() {
		// JetpackTracking::record_user_event( 'jpc_register_begin' );
		// add_action( 'pre_update_jetpack_option_register', array( 'Jetpack_Options', 'delete_option' ) );
		$secrets = self::generate_secrets( 'register' );
		if (
			empty( $secrets['secret_1'] ) ||
			empty( $secrets['secret_2'] ) ||
			empty( $secrets['exp'] )
		) {
			return new WP_Error( 'test_error' ); //new Jetpack_Error( 'missing_secrets' );
		}
		// better to try (and fail) to set a higher timeout than this system
		// supports than to have register fail for more users than it should
		$timeout = 30; //Jetpack::set_min_time_limit( 60 ) / 2;
		$gmt_offset = get_option( 'gmt_offset' );
		if ( ! $gmt_offset ) {
			$gmt_offset = 0;
		}
		// $stats_options = get_option( 'stats_options' );
		// $stats_id = isset($stats_options['blog_id']) ? $stats_options['blog_id'] : null;
		// $tracks_identity = jetpack_tracks_get_identity( get_current_user_id() );
		$args = array(
			'method'  => 'POST',
			'body'    => array(
				'siteurl'         => site_url(),
				'home'            => home_url(),
				'gmt_offset'      => $gmt_offset,
				'timezone_string' => (string) get_option( 'timezone_string' ),
				'site_name'       => (string) get_option( 'blogname' ),
				'secret_1'        => $secrets['secret_1'],
				'secret_2'        => $secrets['secret_2'],
				'site_lang'       => get_locale(),
				'timeout'         => $timeout,
				// 'stats_id'        => $stats_id,
				'state'           => get_current_user_id(),
				// '_ui'             => $tracks_identity['_ui'],
				// '_ut'             => $tracks_identity['_ut'],
				'site_created'    => '2017-01-31 18:21:05', //Jetpack::get_assumed_site_creation_date(),
				'jetpack_version' => JETPACK__VERSION
			),
			'headers' => array(
				'Accept' => 'application/json',
			),
			'timeout' => $timeout,
		);
		//self::apply_activation_source_to_args( $args['body'] );

		$response = wp_remote_request( self::api_url( 'register' ), $args ); //Jetpack_Client::_wp_remote_request( Jetpack::fix_url_for_bad_hosts( Jetpack::api_url( 'register' ) ), $args, true );
		// Make sure the response is valid and does not contain any Jetpack errors
		$registration_details = self::init()->validate_remote_register_response( $response );
		if ( is_wp_error( $registration_details ) ) {
			return $registration_details;
		} elseif ( ! $registration_details ) {
			return new WP_Error( 'test_error' ); //new Jetpack_Error( 'unknown_error', __( 'Unknown error registering your Jetpack site', 'jetpack' ), wp_remote_retrieve_response_code( $response ) );
		}
		if ( empty( $registration_details->jetpack_secret ) || ! is_string( $registration_details->jetpack_secret ) ) {
			return new WP_Error( 'test_error' ); //new Jetpack_Error( 'jetpack_secret', '', wp_remote_retrieve_response_code( $response ) );
		}
		if ( isset( $registration_details->jetpack_public ) ) {
			$jetpack_public = (int) $registration_details->jetpack_public;
		} else {
			$jetpack_public = false;
		}
		// Jetpack_Options::update_options(
		// 	array(
		// 		'id'         => (int)    $registration_details->jetpack_id,
		// 		'blog_token' => (string) $registration_details->jetpack_secret,
		// 		'public'     => $jetpack_public,
		// 	)
		// );
		self::update_option( 'id', (int) $registration_details->jetpack_id );
		self::update_option( 'public', $jetpack_public );
		self::update_option( 'blog_token', (string) $registration_details->jetpack_secret );
		// /**
		//  * Fires when a site is registered on WordPress.com.
		//  *
		//  * @since 3.7.0
		//  *
		//  * @param int $json->jetpack_id Jetpack Blog ID.
		//  * @param string $json->jetpack_secret Jetpack Blog Token.
		//  * @param int|bool $jetpack_public Is the site public.
		//  */
		// do_action( 'jetpack_site_registered', $registration_details->jetpack_id, $registration_details->jetpack_secret, $jetpack_public );
		// Initialize Jump Start for the first and only time.
		// if ( ! Jetpack_Options::get_option( 'jumpstart' ) ) {
		// 	Jetpack_Options::update_option( 'jumpstart', 'new_connection' );
		// 	$jetpack = Jetpack::init();
		// 	$jetpack->stat( 'jumpstart', 'unique-views' );
		// 	$jetpack->do_stats( 'server_side' );
		// };
		return true;
	}

	// https://github.com/Automattic/jetpack/blob/50b583b8ee7366dd958f8611c5d9407c4a2290a3/class.jetpack.php#L5415
	public function validate_remote_register_response( $response ) {
		if ( is_wp_error( $response ) ) {
			  return new WP_Error( 'test_error' ); //new Jetpack_Error( 'register_http_request_failed', $response->get_error_message() );
		  }
		  $code   = wp_remote_retrieve_response_code( $response );
		  $entity = wp_remote_retrieve_body( $response );
		  if ( $entity )
			  $registration_response = json_decode( $entity );
		  else
			  $registration_response = false;
		  $code_type = intval( $code / 100 );
		  if ( 5 == $code_type ) {
			  return new WP_Error( 'test_error' ); //new Jetpack_Error( 'wpcom_5??', sprintf( __( 'Error Details: %s', 'jetpack' ), $code ), $code );
		  } elseif ( 408 == $code ) {
			  return new WP_Error( 'test_error' ); //new Jetpack_Error( 'wpcom_408', sprintf( __( 'Error Details: %s', 'jetpack' ), $code ), $code );
		  } elseif ( ! empty( $registration_response->error ) ) {
			  if ( 'xml_rpc-32700' == $registration_response->error && ! function_exists( 'xml_parser_create' ) ) {
				  $error_description = __( "PHP's XML extension is not available. Jetpack requires the XML extension to communicate with WordPress.com. Please contact your hosting provider to enable PHP's XML extension.", 'jetpack' );
			  } else {
				  $error_description = isset( $registration_response->error_description ) ? sprintf( __( 'Error Details: %s', 'jetpack' ), (string) $registration_response->error_description ) : '';
			  }
			  return new WP_Error( 'test_error' ); //new Jetpack_Error( (string) $registration_response->error, $error_description, $code );
		  } elseif ( 200 != $code ) {
			  return new WP_Error( 'test_error' ); //new Jetpack_Error( 'wpcom_bad_response', sprintf( __( 'Error Details: %s', 'jetpack' ), $code ), $code );
		  }
		  // Jetpack ID error block
		  if ( empty( $registration_response->jetpack_id ) ) {
			  return new WP_Error( 'test_error' ); //new Jetpack_Error( 'jetpack_id', sprintf( __( 'Error Details: Jetpack ID is empty. Do not publicly post this error message! %s', 'jetpack' ), $entity ), $entity );
		  } elseif ( ! is_scalar( $registration_response->jetpack_id ) ) {
			  return new WP_Error( 'test_error' ); //new Jetpack_Error( 'jetpack_id', sprintf( __( 'Error Details: Jetpack ID is not a scalar. Do not publicly post this error message! %s', 'jetpack' ) , $entity ), $entity );
		  } elseif ( preg_match( '/[^0-9]/', $registration_response->jetpack_id ) ) {
			  return new WP_Error( 'test_error' ); //new Jetpack_Error( 'jetpack_id', sprintf( __( 'Error Details: Jetpack ID begins with a numeral. Do not publicly post this error message! %s', 'jetpack' ) , $entity ), $entity );
		  }
		  return $registration_response;
	  }

	// https://github.com/Automattic/jetpack/blob/50b583b8ee7366dd958f8611c5d9407c4a2290a3/class.jetpack.php#L5309
	public static function generate_secrets( $action, $user_id = false, $exp = 600 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		$secret_name  = 'jetpack_' . $action . '_' . $user_id;
		$secrets      = get_option( 'jetpack_secrets', array() ); //Jetpack_Options::get_raw_option( 'jetpack_secrets', array() );
		if (
			isset( $secrets[ $secret_name ] ) &&
			$secrets[ $secret_name ]['exp'] > time()
		) {
			return $secrets[ $secret_name ];
		}
		$secret_value = array(
			'secret_1'  => wp_generate_password( 32, false ),
			'secret_2'  => wp_generate_password( 32, false ),
			'exp'       => time() + $exp,
		);
		$secrets[ $secret_name ] = $secret_value;
		update_option( 'jetpack_secrets', $secrets );//Jetpack_Options::update_raw_option( 'jetpack_secrets', $secrets );
		return $secrets[ $secret_name ];
	}

	// https://github.com/Automattic/jetpack/blob/50b583b8ee7366dd958f8611c5d9407c4a2290a3/class.jetpack.php#L5336
	public static function get_secrets( $action, $user_id ) {
		$secret_name = 'jetpack_' . $action . '_' . $user_id;
		$secrets = get_option( 'jetpack_secrets', array() );
		if ( ! isset( $secrets[ $secret_name ] ) ) {
			return new WP_Error( 'verify_secrets_missing', 'Verification secrets not found' );
		}
		if ( $secrets[ $secret_name ]['exp'] < time() ) {
			self::delete_secrets( $action, $user_id );
			return new WP_Error( 'verify_secrets_expired', 'Verification took too long' );
		}
		return $secrets[ $secret_name ];
	}

	// https://github.com/Automattic/jetpack/blob/50b583b8ee7366dd958f8611c5d9407c4a2290a3/class.jetpack.php#L5352
	public static function delete_secrets( $action, $user_id ) {
		$secret_name = 'jetpack_' . $action . '_' . $user_id;
		$secrets = get_option( 'jetpack_secrets', array() );
		if ( isset( $secrets[ $secret_name ] ) ) {
			unset( $secrets[ $secret_name ] );
			update_option( 'jetpack_secrets', $secrets );
		}
	}

	// https://github.com/Automattic/jetpack/blob/50b583b8ee7366dd958f8611c5d9407c4a2290a3/class.jetpack.php#L4688
	static function sign_role( $role, $user_id = null ) {
		if ( empty( $user_id ) ) {
			$user_id = (int) get_current_user_id();
		}
		if ( ! $user_id  ) {
			return false;
		}
		$token = self::get_access_token();
		if ( ! $token || is_wp_error( $token ) ) {
			return false;
		}
		return $role . ':' . hash_hmac( 'md5', "{$role}|{$user_id}", $token->secret );
	}

	// https://github.com/Automattic/jetpack/blob/c3ab561f5fe5f87f6bdcbde4f39c0d70579a1d99/class.jetpack-data.php#L9
	public static function get_access_token( $user_id = false ) {
		if ( $user_id ) {
			if ( !$tokens = self::get_option( 'user_tokens' ) ) {
				return false;
			}
			if ( $user_id === JETPACK_MASTER_USER ) {
				if ( !$user_id = self::get_option( 'master_user' ) ) {
					return false;
				}
			}
			if ( !isset( $tokens[$user_id] ) || !$token = $tokens[$user_id] ) {
				return false;
			}
			$token_chunks = explode( '.', $token );
			if ( empty( $token_chunks[1] ) || empty( $token_chunks[2] ) ) {
				return false;
			}
			if ( $user_id != $token_chunks[2] ) {
				return false;
			}
			$token = "{$token_chunks[0]}.{$token_chunks[1]}";
		} else {
			$token = self::get_option( 'blog_token' );
			if ( empty( $token ) ) {
				return false;
			}
		}
		return (object) array(
			'secret' => $token,
			'external_user_id' => (int) $user_id,
		);
	}

	// https://github.com/Automattic/jetpack/blob/50b583b8ee7366dd958f8611c5d9407c4a2290a3/class.jetpack.php#L4719
	public static function build_connect_url( $raw = false, $redirect = false, $from = false, $register = false ) {
		$site_id = self::get_option( 'id' );
		$token = self::get_option( 'blog_token' );

		if ( $register || ! $token || ! $site_id ) {
			$url = add_query_arg( array(
				'page' => 'jetpack',
				'action' => 'register',
				'_wpnonce' => wp_create_nonce( 'jetpack-register' )
			), admin_url( 'admin.php' ) );

			if ( ! empty( $redirect ) ) {
				$url = add_query_arg(
					'redirect',
					urlencode( wp_validate_redirect( esc_url_raw( $redirect ) ) ),
					$url
				);
			}

			// if( is_network_admin() ) {
			// 	$url = add_query_arg( 'is_multisite', network_admin_url( 'admin.php?page=jetpack-settings' ), $url );
			// }
		} else {
			// // Let's check the existing blog token to see if we need to re-register. We only check once per minute
			// // because otherwise this logic can get us in to a loop.
			// $last_connect_url_check = intval( Jetpack_Options::get_raw_option( 'jetpack_last_connect_url_check' ) );
			// if ( ! $last_connect_url_check || ( time() - $last_connect_url_check ) > MINUTE_IN_SECONDS ) {
			// 	Jetpack_Options::update_raw_option( 'jetpack_last_connect_url_check', time() );
			// 	$response = Jetpack_Client::wpcom_json_api_request_as_blog(
			// 		sprintf( '/sites/%d', $site_id ) .'?force=wpcom',
			// 		'1.1'
			// 	);
			// 	if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// 		// Generating a register URL instead to refresh the existing token
			// 		return $this->build_connect_url( $raw, $redirect, $from, true );
			// 	}
			// }
			// if ( defined( 'JETPACK__GLOTPRESS_LOCALES_PATH' ) && include_once JETPACK__GLOTPRESS_LOCALES_PATH ) {
			// 	$gp_locale = GP_Locales::by_field( 'wp_locale', get_locale() );
			// }
			$role = 'administrator'; //self::translate_current_user_to_role();
			$signed_role = self::sign_role( $role );
			$user = wp_get_current_user();
			$jetpack_admin_page = esc_url_raw( admin_url( 'admin.php?page=jetpack' ) );
			$redirect = $redirect
				? wp_validate_redirect( esc_url_raw( $redirect ), $jetpack_admin_page )
				: $jetpack_admin_page;
			// if( isset( $_REQUEST['is_multisite'] ) ) {
			// 	$redirect = Jetpack_Network::init()->get_url( 'network_admin_page' );
			// }
			$secrets = self::generate_secrets( 'authorize', false, 2 * HOUR_IN_SECONDS );
			/**
			 * Filter the type of authorization.
			 * 'calypso' completes authorization on wordpress.com/jetpack/connect
			 * while 'jetpack' ( or any other value ) completes the authorization at jetpack.wordpress.com.
			 *
			 * @since 4.3.3
			 *
			 * @param string $auth_type Defaults to 'calypso', can also be 'jetpack'.
			 */
			$auth_type = apply_filters( 'jetpack_auth_type', 'calypso' );
			//$tracks_identity = jetpack_tracks_get_identity( get_current_user_id() );
			$args = urlencode_deep(
				array(
					'response_type' => 'code',
					'client_id'     => self::get_option( 'id' ),
					'redirect_uri'  => add_query_arg(
						array(
							'action'   => 'authorize',
							'_wpnonce' => wp_create_nonce( "jetpack-authorize_{$role}_{$redirect}" ),
							'redirect' => urlencode( $redirect ),
						),
						esc_url( admin_url( 'admin.php?page=jetpack' ) )
					),
					'state'         => $user->ID,
					'scope'         => $signed_role,
					'user_email'    => $user->user_email,
					'user_login'    => $user->user_login,
					'is_active'     => ( bool ) self::get_access_token(),//Jetpack::is_active(),
					'jp_version'    => JETPACK__VERSION,
					'auth_type'     => $auth_type,
					'secret'        => $secrets['secret_1'],
					'locale'        => '', //( isset( $gp_locale ) && isset( $gp_locale->slug ) ) ? $gp_locale->slug : '',
					'blogname'      => get_option( 'blogname' ),
					'site_url'      => site_url(),
					'home_url'      => home_url(),
					'site_icon'     => get_site_icon_url(),
					'site_lang'     => get_locale(),
					// '_ui'           => $tracks_identity['_ui'],
					// '_ut'           => $tracks_identity['_ut'],
					'site_created'  => '2017-01-31 18:21:05', //Jetpack::get_assumed_site_creation_date(),
				)
			);
			//self::apply_activation_source_to_args( $args );
			$url = add_query_arg( $args, self::api_url( 'authorize' ) );
		}

		if ( $from ) {
			$url = add_query_arg( 'from', $from, $url );
		}

		return $raw ? $url : esc_url( $url );
	}

	// https://github.com/Automattic/jetpack/blob/50b583b8ee7366dd958f8611c5d9407c4a2290a3/class.jetpack.php#L5118
	public static function api_url( $relative_url ) {
		return trailingslashit( JETPACK__API_BASE . $relative_url  ) . JETPACK__API_VERSION . '/';
	}

	// https://github.com/Automattic/jetpack/blob/50b583b8ee7366dd958f8611c5d9407c4a2290a3/class.jetpack.php#L2270
	public static function update_user_token( $user_id, $token, $is_master_user ) {
		// not designed for concurrent updates
		$user_tokens = self::get_option( 'user_tokens' );
		if ( ! is_array( $user_tokens ) )
			$user_tokens = array();
		$user_tokens[$user_id] = $token;
		if ( $is_master_user ) {
			$master_user = $user_id;
			//$options     = compact( 'user_tokens', 'master_user' );
			return self::update_option( 'user_tokens', $user_tokens ) && self::update_option( 'master_user', $master_user );
		} else {
			return self::update_option( 'user_tokens', $user_tokens );
			//$options = compact( 'user_tokens' );
		}
		//return Jetpack_Options::update_options( $options );
	}

	private static function get_option_group_name( $option ) {
		if ( 'id' === $option || 'public' === $option || 'master_user' === $option ) {
			return 'jetpack_options';
		}
		if ( 'blog_token' === $option || 'user_tokens' === $option ) {
			return 'jetpack_private_options';
		}
		return false;
	}

	public static function get_option( $option, $default ) {
		if ( false === ( $group = self::get_option_group_name( $option ) ) ) { return false; }
		$compact_options = get_option( $group, array() );
		return isset( $compact_options[$option] ) ? $compact_options[$option] : $default;
	}

	public static function update_option( $option, $value ) {
		if ( false === ( $group = self::get_option_group_name( $option ) ) ) { return false; }
		$compact_options = get_option( $group, array() );
		return update_option( $group, array_merge( $compact_options, array( $option => $value ) ) );
	}
}
