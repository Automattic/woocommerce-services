<?php
$permissions     = 'read_write';
$defaultConfig   = json_decode( file_get_contents( __DIR__ . '/../e2e/config/default.json' ), true );
$consumer_key    = $defaultConfig[ 'consumerKey' ];
$consumer_secret = $defaultConfig[ 'consumerSecret' ];

$data = array(
	'user_id'         => 1,
	'permissions'     => $permissions,
	'consumer_key'    => wc_api_hash( $consumer_key ),
	'consumer_secret' => $consumer_secret,
	'truncated_key'   => substr( $consumer_key, -7 ),
);

global $wpdb;
$wpdb->insert(
	$wpdb->prefix . 'woocommerce_api_keys',
	$data,
	array(
		'%d',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
	)
);
