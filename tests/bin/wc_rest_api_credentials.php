<?php
$permissions = 'read_write';
$consumer_key = 'wc_rest_api_consumer_key';

$data = array(
	'user_id'         => 1,
	'permissions'     => $permissions,
	'consumer_key'    => $consumer_key,
	'consumer_secret' => 'wc_rest_api_consumer_secret',
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
