<?php
/*
 * WordPress Custom API Data Hooks
 */

if ( !defined( 'ABSPATH' ) ) exit;

add_filter( 'user_contactmethods',function($userInfo) {
	$userInfo['steemId'] 				= __( 'Steem ID' );
	return $userInfo;
});

?>
