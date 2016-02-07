<?php

if ( ! class_exists( 'React_Test' ) ) :

class React_Test {

	public function __construct() {

		add_action( 'admin_menu', array( $this, 'add_page' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );

	}

	function add_scripts() {

		wp_register_script( 'wc_connect_shipping_admin', plugins_url( 'build/bundle.js', dirname( __FILE__ ) ), array() );

		$schema = json_decode( file_get_contents( dirname( __FILE__ ) . '/../src/usps.json' ) );

		$admin_array = array(
			'foo'  => 'bar',
			'usps' => $schema
		);

		wp_localize_script( 'wc_connect_shipping_admin', 'wcConnectData', $admin_array );
		wp_enqueue_script( 'wc_connect_shipping_admin' );

	}

	function add_page() {

		add_management_page( 'React Test', 'React Test', 'manage_options', 'react-test', array( $this, 'render_page' ) );

	}

	function render_page() {
		?>
		<div id="wc-connect-admin-container">
			React goes here
		</div>
		<?php
	}

}

new React_Test();

endif;