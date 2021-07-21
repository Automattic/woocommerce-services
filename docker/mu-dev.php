<?php

function wc_connect_jetpack_dev_access_token() {
	$token                   = new stdClass();
	$token->secret           = 'askjdflasjdlfkajsldfkjaslkdf.asdkfjlaskjdflajskd';
	$token->external_user_id = 0;

	return $token;
}

add_filter( 'wc_connect_jetpack_access_token', 'wc_connect_jetpack_dev_access_token' );
