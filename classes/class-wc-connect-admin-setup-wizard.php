<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Admin_Setup_Wizard' ) ) :

/**
 * WC_Connect_Admin_Setup_Wizard class.
 */
class WC_Connect_Admin_Setup_Wizard {

	const PAGE_SLUG = 'wcc-setup';

	/** @var string Current Step */
	private $step   = '';

	/** @var array Steps for the setup wizard */
	private $steps  = array();

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		if ( apply_filters( 'woocommerce_connect_enable_setup_wizard', true ) && current_user_can( 'manage_woocommerce' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menus' ) );
			$this->setup_wizard();
		}
	}

	/**
	 * Add admin menus/screens.
	 */
	public function admin_menus() {
		add_dashboard_page( '', '', 'manage_options', self::PAGE_SLUG, '' );
	}

	/**
	 * Show the setup wizard.
	 */
	public function setup_wizard() {
		if ( empty( $_GET['page'] ) || self::PAGE_SLUG !== $_GET['page'] ) {
			return;
		}

		require_once( ABSPATH . '/wp-admin/includes/plugin-install.php' );
		require_once( ABSPATH . '/wp-admin/includes/class-wp-upgrader.php' );
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		require_once( ABSPATH . '/wp-admin/includes/class-plugin-upgrader.php' );
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		require_once( ABSPATH . '/wp-admin/includes/misc.php' );
		require_once( ABSPATH . '/wp-admin/includes/class-wp-upgrader-skin.php' );
		require_once( ABSPATH . '/wp-admin/includes/class-automatic-upgrader-skin.php' );

		$this->steps = array(
			'introduction' => array(
				'name'    =>  __( 'Introduction', 'woocommerce' ),
				'view'    => array( $this, 'wcc_setup_introduction' ),
				'handler' => '',
			),
			'jetpack' => array(
				'name'    =>  __( 'Install Jetpack', 'woocommerce' ),
				'view'    => array( $this, 'wcc_setup_install_jetpack' ),
				'handler' => array( $this, 'wcc_setup_install_jetpack_save' ),
			),
			'connect_jetpack' => array(
				'name'    =>  __( 'Connect Jetpack', 'woocommerce' ),
				'view'    => array( $this, 'wcc_setup_connect_jetpack' ),
				'handler' => '',
			),
			'next_steps' => array(
				'name'    =>  __( 'Ready!', 'woocommerce' ),
				'view'    => array( $this, 'wcc_setup_ready' ),
				'handler' => '',
			)
		);
		$this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );
		$suffix     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'select2', WC()->plugin_url() . '/assets/js/select2/select2' . $suffix . '.js', array( 'jquery' ), '3.5.2' );
		wp_register_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $suffix . '.js', array( 'jquery', 'select2' ), WC_VERSION );
		wp_localize_script( 'wc-enhanced-select', 'wc_enhanced_select_params', array(
			'i18n_matches_1'            => _x( 'One result is available, press enter to select it.', 'enhanced select', 'woocommerce' ),
			'i18n_matches_n'            => _x( '%qty% results are available, use up and down arrow keys to navigate.', 'enhanced select', 'woocommerce' ),
			'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
			'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
			'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
			'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
			'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
			'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
			'ajax_url'                  => admin_url( 'admin-ajax.php' ),
			'search_products_nonce'     => wp_create_nonce( 'search-products' ),
			'search_customers_nonce'    => wp_create_nonce( 'search-customers' )
		) );
		wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
		wp_enqueue_style( 'wc-setup', WC()->plugin_url() . '/assets/css/wc-setup.css', array( 'dashicons', 'install' ), WC_VERSION );

		wp_register_script( 'wc-setup', WC()->plugin_url() . '/assets/js/admin/wc-setup.min.js', array( 'jquery', 'wc-enhanced-select', 'jquery-blockui' ), WC_VERSION );
		wp_localize_script( 'wc-setup', 'wc_setup_params', array(
			'locale_info' => json_encode( include( WC()->plugin_path() . '/i18n/locale-info.php' ) )
		) );

		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'] );
		}

		ob_start();
		$this->setup_wizard_header();
		$this->setup_wizard_steps();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;
	}

	public function get_next_step_link() {
		$keys = array_keys( $this->steps );
		return add_query_arg( 'step', $keys[ array_search( $this->step, array_keys( $this->steps ) ) + 1 ] );
	}

	/**
	 * Setup Wizard Header.
	 */
	public function setup_wizard_header() {
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta name="viewport" content="width=device-width" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php _e( 'WooCommerce &rsaquo; Setup Wizard', 'woocommerce' ); ?></title>
		<?php wp_print_scripts( 'wc-setup' ); ?>
		<?php do_action( 'admin_print_styles' ); ?>
		<?php do_action( 'admin_head' ); ?>
	</head>
	<body class="wc-setup wp-core-ui">
	<h1 id="wc-logo"><a href="https://woothemes.com/woocommerce"><img src="<?php echo WC()->plugin_url(); ?>/assets/images/woocommerce_logo.png" alt="WooCommerce" /></a></h1>
	<?php
	}

	/**
	 * Setup Wizard Footer.
	 */
	public function setup_wizard_footer() {
	?>
	<?php if ( 'next_steps' === $this->step ) : ?>
		<a class="wc-return-to-dashboard" href="<?php echo esc_url( admin_url() ); ?>"><?php _e( 'Return to the WordPress Dashboard', 'woocommerce' ); ?></a>
	<?php endif; ?>
	</body>
	</html>
	<?php
}

	/**
	 * Output the steps.
	 */
	public function setup_wizard_steps() {
		$ouput_steps = $this->steps;
		array_shift( $ouput_steps );
		?>
		<ol class="wc-setup-steps">
			<?php foreach ( $ouput_steps as $step_key => $step ) : ?>
				<li class="<?php
				if ( $step_key === $this->step ) {
					echo 'active';
				} elseif ( array_search( $this->step, array_keys( $this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
					echo 'done';
				}
				?>"><?php echo esc_html( $step['name'] ); ?></li>
			<?php endforeach; ?>
		</ol>
		<?php
	}

	/**
	 * Output the content for the current step.
	 */
	public function setup_wizard_content() {
		echo '<div class="wc-setup-content">';
		call_user_func( $this->steps[ $this->step ]['view'] );
		echo '</div>';
	}

	/**
	 * Actions on the final step.
	 */
	private function wcc_setup_ready_actions() {
		WC_Admin_Notices::remove_notice( 'install' );

		if ( isset( $_GET['wc_tracker_optin'] ) && isset( $_GET['wc_tracker_nonce'] ) && wp_verify_nonce( $_GET['wc_tracker_nonce'], 'wc_tracker_optin' ) ) {
			update_option( 'woocommerce_allow_tracking', 'yes' );
			WC_Tracker::send_tracking_data( true );

		} elseif ( isset( $_GET['wc_tracker_optout'] ) && isset( $_GET['wc_tracker_nonce'] ) && wp_verify_nonce( $_GET['wc_tracker_nonce'], 'wc_tracker_optout' ) ) {
			update_option( 'woocommerce_allow_tracking', 'no' );
		}
	}

	/**
	 * Final step.
	 */
	public function wcc_setup_ready() {
		$this->wcc_setup_ready_actions();
		?>
		<h1><?php _e( 'Your Connect for WooCommerce is Ready!', 'woocommerce' ); ?></h1>

		<?php if ( 'unknown' === get_option( 'woocommerce_allow_tracking', 'unknown' ) ) : ?>
			<div class="woocommerce-message woocommerce-tracker">
				<p><?php printf( __( 'Want to help make WooCommerce even more awesome? Allow WooThemes to collect non-sensitive diagnostic data and usage information. %sFind out more%s.', 'woocommerce' ), '<a href="https://woocommerce.com/usage-tracking/" target="_blank">', '</a>' ); ?></p>
				<p class="submit">
					<a class="button-primary button button-large" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc_tracker_optin', 'true' ), 'wc_tracker_optin', 'wc_tracker_nonce' ) ); ?>"><?php _e( 'Allow', 'woocommerce' ); ?></a>
					<a class="button-secondary button button-large skip"  href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc_tracker_optout', 'true' ), 'wc_tracker_optout', 'wc_tracker_nonce' ) ); ?>"><?php _e( 'No thanks', 'woocommerce' ); ?></a>
				</p>
			</div>
		<?php endif; ?>

		<p class="wc-setup-actions step">
			<a href="<?php echo esc_url( admin_url() ); ?>" class="button-primary button button-large button-next"><?php _e( 'Go to Dashboard', 'woocommerce' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Introduction step.
	 */
	public function wcc_setup_introduction() {
		?>
		<h1><?php _e( 'Welcome to the world of Connect for WooCommerce!', 'woocommerce' ); ?></h1>
		<p><?php _e( 'Thank you for choosing Connect for WooCommerce to supercharge your WooCommerce store! This quick setup wizard will help you configure the basic settings. <strong>It’s completely optional and shouldn’t take longer than five minutes.</strong>', 'woocommerce' ); ?></p>
		<p><?php _e( 'No time right now? If you don’t want to go through the wizard, you can skip and return to the WordPress dashboard. Come back anytime if you change your mind!', 'woocommerce' ); ?></p>
		<p class="wc-setup-actions step">
			<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-large button-next"><?php _e( 'Let\'s Go!', 'woocommerce' ); ?></a>
			<a href="<?php echo esc_url( admin_url() ); ?>" class="button button-large"><?php _e( 'Not right now', 'woocommerce' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Install Jetpack explanation.
	 */
	public function wcc_setup_install_jetpack() {
		?>
		<h1><?php _e( 'Jetpack Plugin', 'woocommerce' ); ?></h1>
		<form method="post">
			<p><?php printf( __( 'Connect for WooCommerce uses %sJetpack%s to do stuff.', 'woocommerce' ), '<a href="https://jetpack.com" target="_blank">', '</a>' ); ?></p>

			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Install Jetpack', 'woocommerce' ); ?>" name="save_step" />
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Install Jetpack plugin.
	 */
	public function wcc_setup_install_jetpack_save() {
		check_admin_referer( 'wc-setup' );

		$api = plugins_api( 'plugin_information', array(
			'slug' => 'jetpack',
			'fields' => array(
				'short_description' => false,
				'sections' => false,
				'requires' => false,
				'rating' => false,
				'ratings' => false,
				'downloaded' => false,
				'last_updated' => false,
				'added' => false,
				'tags' => false,
				'compatibility' => false,
				'homepage' => false,
				'donate_link' => false,
			),
		) );

		if ( is_wp_error( $api ) ) {
			wp_die( $api );
		}

		ob_start();

		$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
		$result = $upgrader->install( $api->download_link ); // to test: 'http://local.wordpress.dev/jetpack.zip'

		ob_end_clean();

		if ( is_wp_error( $result ) ) {
			wp_die( $result );
		}

		$result = activate_plugin( 'jetpack/jetpack.php' );

		if ( is_wp_error( $result ) ) {
			wp_die( $result );
		}

		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Connect Jetpack step.
	 */
	public function wcc_setup_connect_jetpack() {
		?>
		<h1><?php _e( 'Connect your Jetpack', 'woocommerce' ); ?></h1>
		<p><?php _e( 'Your Jetpack needs to be connected.', 'woocommerce' ); ?></p>
		<p class="wc-setup-actions step">
			<?php $connect_url = Jetpack::init()->build_connect_url( false, get_site_url( null, $this->get_next_step_link() ), 'wcc' ); ?>
			<a href="<?php echo esc_url( $connect_url ); ?>" class="button-primary button button-large button-next"><?php _e( 'Connect Jetpack', 'woocommerce' ); ?></a>
		</p>
		<?php
	}

}

new WC_Connect_Admin_Setup_Wizard();

endif;