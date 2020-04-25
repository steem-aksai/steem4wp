<?php

/**
 * REST API: WP_REST_Authentication_Controller class
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.7.0
 */
if (!defined('ABSPATH')) exit;

class Steem4Everyone
{

  protected static $steem4everyone_base_url = 'https://steem4everyone-api.herokuapp.com/api';

  protected $node;

  /**
   * Initialize the connection to the host
   *
   * @param      string  $node   The node you want to connect
   */
  public function __construct($node = null)
  {
    if (!empty($node)) {
      $this->node = trim($node);
    } else {
      $this->node = null;
    }
  }

  public function send($endpoint, $body)
  {
    if (function_exists('get_option')) {
      // $dapp_id = get_option('steem_dapp_account');
      $dapp_password = get_option('steem_dapp_steemid_password');
      $body['dapp_password'] = $dapp_password;
      // $dapp_secret = get_option('steem_dapp_steemid_secret');
    }
    // if (empty($dapp_id) || empty($dapp_password) || empty($dapp_secret)) {
    //   return null;
    // }

    if (!empty($this->node)) {
      $body['api_url'] = $this->node;
    }

    global $wp_version;
    $args = array(
      'method'      => 'POST',
      'timeout'     => 60,
      'redirection' => 5,
      'httpversion' => '1.0',
      'blocking'    => true,
      'headers'     => array(
        'Referer' => get_bloginfo('url'),
        'Origin'  => get_bloginfo('url'),
      ),
      'body'        => $body,
      'cookies'     => array(),
      'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
    );

    $response = wp_remote_post(Steem4Everyone::$steem4everyone_base_url . $endpoint, $args);

    if (is_wp_error($response)) {
      return null;
    } else {
      $body = wp_remote_retrieve_body($response);
      $data = json_decode($body, true);
      return $data;
    }
  }

  /**
   * Broadcast the operation to Steem blockchain
   */
  public function broadcast($user, $operation, $params)
  {
    $res = $this->send('/broadcast', [
      'user' => $user,
      'operations' => json_encode([[$operation, $params]])
    ]);
    if (array_key_exists('result', $res) && !empty($res['result'])) {
      return $res['result'];
    } else {
      return $res;
    }
  }

  /**
   * Create a comment on Steem
   */
  public function comment($parentAuthor, $parentPermlink, $author, $permlink, $title, $body, $jsonMetadata)
  {
    return $this->broadcast($author, 'comment', [
      'parent_author' => $parentAuthor,
      'parent_permlink' => $parentPermlink,
      'author' => $author,
      'permlink' => $permlink,
      'title' => $title,
      'body' => $body,
      'json_metadata' => json_encode($jsonMetadata)
    ]);
  }

  /**
   * Get the profile info of the current account
   */
  public function me($user)
  {
    return $this->send('/me', [
      'user' => $user
    ]);
  }

}
