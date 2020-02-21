<?php
$permissions     = 'read_write';
$consumer_key    = 'ck_' . 'wc_rest_api_consumer_key';
$consumer_secret = 'cs_' . 'wc_rest_api_consumer_secret';

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
