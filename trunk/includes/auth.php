<?php
$auth = yourls_is_valid_user();

if( $auth !== true ) {

	$format = ( isset($_REQUEST['format']) ? $_REQUEST['format'] : null );
	
	// API mode, 
	if ( defined('YOURLS_API') && YOURLS_API == true ) {
		yourls_api_output( $format, array('shorturl' => $auth) );

	// Regular mode
	} else {
		yourls_login_screen( $auth );
	}
	
	die();
}