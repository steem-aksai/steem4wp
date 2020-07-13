<?php

/**
 * Steem API: WP_Steem_Ops class
 *
 * @package WordPress
 * @subpackage STEEM_API
 * @since 4.7.0
 */
if (!defined('ABSPATH')) exit;

/**
 * Core class to access posts via the REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Controller
 */
class WP_Steem_Ops
{

  /**
   * @var $steem
   *
   * $steem will be the Steem object that interact with Steem blockchain
   */
  protected $steem;

  /**
   * Constructor.
   *
   * @since 4.7.0
   */
  public function __construct()
  {
    $this->steem = new Steem();
  }

  public function create_post($author, $post_id, $tags = null, $footer = null)
  {
    if ($this->steem) {
      $post = get_post($post_id);
      $author_old = get_post_meta($post_id, 'steem_author', true);
      $permlink = get_post_meta($post_id, 'steem_permlink', true);
      $video_url = get_post_meta($post_id, 'video', true); // mini-program video meta
      $content = $post->post_content;
      if (!empty($video_url)) {
        $content = $content . "\n" . $video_url;
      }
      if (!empty($footer)) {
        $content = $content . "\n" . $footer;
      }
      if (empty($author_old) && empty($permlink)) {
        $tx = $this->steem->createPost($author, $post->post_title, $content, $tags);
      } else if (!empty($author_old) && !empty($permlink) && $author == $author_old) {
        $tx = $this->steem->createPost($author, $post->post_title, $content, $tags, null, null, $permlink);
      } else {
        return true;
      }
      if (!empty($tx) && array_key_exists('operations', $tx) && !array_key_exists('trace', $tx)) {
        $operation = $tx['operations'][0][1];
        update_post_meta($post_id, 'steem_author', $operation['author']);
        update_post_meta($post_id, 'steem_permlink', $operation['permlink']);
        write_log("createPost Succeeded");
        write_log($operation);
        write_log("----------");

        // second steem post
        $this->create_2nd_post($operation['author'], $operation['permlink'], $post->post_title, $content, $tags);

        return true;
      } else {
        write_log("createPost Failed");
        write_log($tx);
        write_log("----------");
      }
    }
    return false;
  }

  protected function create_2nd_post($author, $permlink, $title, $body, $tags)
  {
    try {
      $node = get_option("steem_2nd_api_node_url");
      if (!empty($node)) {
        $second_steem = new Steem($node);
        if ($second_steem) {
          write_log("createPost 2nd: @{$author}/{$permlink}");
          $tx = $second_steem->createPost($author, $title, $body, $tags, null, null, $permlink);
          return $tx;
        } else {
          return null;
        }
      }
    } catch (\Exception $e) {
      write_log("failed to create post @{$author}/{$permlink} on second Steem");
      return $e;
    }
  }
}
