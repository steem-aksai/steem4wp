<?php

/**
 * REST API: WP_REST_Authentication_Controller class
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.7.0
 */
if (!defined('ABSPATH')) exit;

use \RobThree\Auth\TwoFactorAuth;

class SteemID
{

  protected static $steemid_base_url = 'https://steemid.herokuapp.com';

  public static function send($endpoint, $wechat_id, $username = '', $profile = '')
  {
    if (function_exists('get_option')) {
      $dapp_id = get_option('steem_dapp_account');
      $dapp_password = get_option('steem_dapp_steemid_password');
      $dapp_secret = get_option('steem_dapp_steemid_secret');
    }
    if (empty($dapp_id) || empty($dapp_password) || empty($dapp_secret)) {
      return null;
    }

    $tfa = new TwoFactorAuth('Steem4WP');
    $token = $tfa->getCode($dapp_secret);

    global $wp_version;
    $args = array(
      'method'      => 'POST',
      'timeout'     => 60,
      'redirection' => 5,
      'httpversion' => '1.0',
      'blocking'    => true,
      'headers'     => array(
        'Referer' => get_bloginfo('url'),
        'Origin'  => get_bloginfo('url')
      ),
      'body'        => array(
        'dapp_id'    => $dapp_id,
        'dapp_key'   => $dapp_password,
        'timestamp'  => (new DateTime())->getTimestamp() * 1000,
        'token'      => $token,
        'wechat_id'  => $wechat_id,
        'username'   => $username,
      ),
      'cookies'     => array(),
      'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
    );
    if (!empty($profile)) {
      $args['body']['json_metadata'] = $profile;
    }
    $response = wp_remote_post(SteemID::$steemid_base_url . $endpoint, $args);
    if (is_wp_error($response)) {
      return null;
    } else {
      $body = wp_remote_retrieve_body($response);
      $data = json_decode($body, true);
      if (array_key_exists('username', $data) && !empty($data['username'])) {
        return $data['username'];
      } else {
        return null;
      }
    }
  }

  /**
   * Find the Steem account by WeChat ID
   */
  public static function find($wechat_id)
  {
    return SteemID::send('/users/wechat/find', $wechat_id);
  }

  /**
   * Bind the Steem account with WeChat ID
   */
  public static function bind($wechat_id, $username)
  {
    return SteemID::send('/users/wechat/bind', $wechat_id, $username);
  }

  /**
   * Register the Steem account with WeChat ID
   */
  public static function new($wechat_id, $username, $profile)
  {
    return SteemID::send('/users/wechat/new', $wechat_id, $username, $profile);
  }
}
