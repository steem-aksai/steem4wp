<?php
/**
 * REST API: WP_REST_Authentication_Controller class
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.7.0
 */
if ( !defined( 'ABSPATH' ) ) exit;

class Steem_Auth {

  /**
	 * Generate session key and expiration date
	 */
	public static function build_session() {
    $session_str = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $session_key = substr( str_shuffle($session_str), mt_rand( 0, strlen($session_str) - 17 ), 16 );
    $expire_in = date('Y-m-d H:i:s',time()+7200);
    $session = array(
      'session_key' => $session_key,
      'expire_in' => $expire_in
    );
    return $session;
  }

  /**
	 * Query user info with session key
	 */
	public static function login( $session ) {
		if( $session ) {
      $user_query = new WP_User_Query( array( 'meta_key' => 'session_key', 'meta_value' => $session ) );
      $users = $user_query->get_results();
      if( ! empty( $users ) ) {
        $user = $users[0];
        return $user;
      } else {
        return false;
      }
    }
    return false;
  }

}
