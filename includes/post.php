<?php

/**
 * REST API: WP_REST_Posts_Controller class
 *
 * @package WordPress
 * @subpackage REST_API
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
class WP_Steem_REST_Post_Router extends WP_REST_Controller
{

  /**
   * Instance of a post meta fields object.
   *
   * @since 4.7.0
   * @var WP_REST_Post_Meta_Fields
   */
  protected $meta;

  /**
   * Endpoint namespace.
   *
   * @var string
   */
  protected $namespace = 'steem/v1';

  /**
   * Route base.
   *
   * @var string
   */
  protected $rest_base = 'posts';

  /**
   * Post type.
   *
   * @var string
   */
  protected $post_type = 'post';

  /**
   * @var $steem
   *
   * $steem will be the Steem object that interact with Steem blockchain
   */
  protected $steem;

  /**
   * @var $steem_pos
   *
   * $steem_pos will be the WP_Steem_Ops object that interact with Steem blockchain
   */
  protected $steem_ops;

  /**
   * Constructor.
   *
   * @since 4.7.0
   */
  public function __construct()
  {

    $this->meta = new WP_REST_Post_Meta_Fields($this->post_type);
  }

  /**
   * Registers the routes for the objects of the controller.
   *
   * @since 4.7.0
   *
   * @see register_rest_route()
   */
  public function register_routes()
  {

    register_rest_route($this->namespace, '/' . $this->rest_base, array(
      array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => array($this, 'create_posts'),
        'permission_callback' => array($this, 'steem_posts_ops_permissions_check'),
        'args'                => $this->steem_post_operation_params(),
      ),
    ));

    register_rest_route($this->namespace, '/' . $this->rest_base, array(
      array(
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => array($this, 'update_posts'),
        'permission_callback' => array($this, 'steem_posts_ops_permissions_check'),
        'args'                => $this->steem_post_operation_params(),
      )
    ));

    register_rest_route($this->namespace, '/' . $this->rest_base, array(
      array(
        'methods'             => WP_REST_Server::DELETABLE,
        'callback'            => array($this, 'delete_posts'),
        'permission_callback' => array($this, 'steem_posts_ops_permissions_check'),
        'args'                => $this->steem_post_operation_params(),
      ),
    ));
  }

  public function steem_posts_ops_permissions_check($request)
  {

    if (empty($request['access_token'])) {
      return new WP_Error('access_token', __('User access token incorrect.'), array('status' => 400));
    }

    if (empty($request['post_id'])) {
      return new WP_Error('post_id', __('Post ID cannot be empty.'), array('status' => 400));
    }

    return true;
  }

  public function create_posts($request)
  {
    $args = $request->get_params();
    $access_token = base64_decode($args['access_token']);
    $users = Steem_Auth::login($access_token);
    if (!$users) {
      return new WP_Error('error', 'User cannot publish content to Steem without authentication', array('status' => 400));
    }
    $user_id = $users->ID;
    $user = get_user_by('ID', $user_id);

    $post_id = $request['post_id'];
    $tags = $args['tags'];
    $tags = $this->collectTags($tags);

    if (!$this->steem_ops) {
      $this->steem_ops = new WP_Steem_Ops();
    }
    $res = $this->steem_ops->create_post($user->user_login, $post_id, $tags);
    if ($res) {
      $result["status"] = 200;
      $result["code"] = "success";
      $result["message"] = 'Post created on Steem sueccessfully';
      return rest_ensure_response($result);
    } else {
      $result["status"] = 500;
      $result["code"] = "error";
      $result["message"] = "Failed to create post on Steem";
      return rest_ensure_response($result);
    }
  }

  public function update_posts($request)
  {
    $access_token = base64_decode($request['access_token']);
    $users = Steem_Auth::login($access_token);
    if (!$users) {
      return new WP_Error('error', 'User hasn\'t logged in, cannot edit post', array('status' => 400));
    }
    $user_id = $users->ID;
    $user = get_user_by('ID', $user_id);

    $post_id = $request['post_id'];

    $args = $request->get_params();
    $tags = $args['tags'];
    $tags = $this->collectTags($tags);

    $post = get_post($post_id);
    $author_id = (int) $post->post_author;
    $post_status = isset($request['post_status']) ? $request['post_status'] : $post->post_status;

    $roles = (array) $user->roles;
    if (($user_id != $author_id) && (in_array('administrator', $user->roles) || in_array('superadmin', $user->roles) || in_array('editor', $user->roles))) {
      return new WP_Error('error', 'You have no right to edit post', array('status' => 400));
    }

    $postarr = array(
      'ID'           => $post_id,
      'post_status'  => $post_status,
    );

    if (!$this->steem_ops) {
      $this->steem_ops = new WP_Steem_Ops();
    }
    $res = $this->steem_ops->create_post($user->user_login, $post_id, $tags);
    if ($res) {
      $result["code"] = "success";
      $result["message"] = "Update succeeded";
      $result["status"] = 200;
    } else {
      $result["code"] = "success";
      $result["message"] = "Update failed";
      $result["status"] = 500;
    }

    return rest_ensure_response($result);
  }

  public function delete_posts($request)
  {
    $access_token = base64_decode($request['access_token']);
    $users = Steem_Auth::login($access_token);
    if (!$users) {
      return new WP_Error('error', 'User hasn\'t logged in, cannot delete post', array('status' => 400));
    }
    $user_id = $users->ID;
    $user = get_user_by('ID', $user_id);

    $post_id = $request["post_id"];
    $post = get_post($post_id);
    $author_id = (int) $post->post_author;

    if (($user_id != $author_id) && (in_array('administrator', $user->roles) || in_array('superadmin', $user->roles) || in_array('editor', $user->roles))) {
      return new WP_Error('error', 'You have no permission to delete post', array('status' => 400));
    }

    if (!$this->steem) {
      $this->steem = new Steem();
    }
    if ($this->steem) {
      $steem_author = get_post_meta($post_id, 'steem_author', true);
      $steem_permlink = get_post_meta($post_id, 'steem_permlink', true);
      if (!empty($steem_author) && !empty($steem_permlink)) {
        $tx = $this->steem->deletePost($steem_author, $steem_permlink);
        // write_log("deletion transaction");
        // write_log($steem_author);
        // write_log($steem_permlink);
        // write_log($tx);
        // write_log("----------");
        if (!empty($tx) && array_key_exists('operations', $tx) && !array_key_exists('trace', $tx)) {
          $result["status"] = 200;
          $result["code"] = "success";
          $result["message"] = "Deletion succeeded";
          $response  = rest_ensure_response($result);
          return $response;
        }
      }
    }

    $result["status"] = 500;
    $result["code"] = "success";
    $result["message"] = "Deletion falied";
    $response  = rest_ensure_response($result);
    return $response;
  }

  /**
   * collect tags by customized tags of user and default tags of admin.
   *
   * @param      string  $customized_tags The tags of customized.
   * @return     array   tags.
   */
  public function collectTags($customized_tags)
  {
    $default_tags = array();
    $tags = array();

    // get default tags.
    if (function_exists('get_option')) {
      $default_tags = get_option("steem_dapp_default_tags");
    }

    // convert default tags to array.
    if (!empty($default_tags) && is_string($default_tags)) {
      $default_tags = strtolower($default_tags);
      $default_tags = wp_parse_list($default_tags);
    }

    if (!empty($customized_tags) && is_string($customized_tags)) {
      $customized_tags = strtolower($customized_tags);
      $customized_tags = wp_parse_list($customized_tags);
    }

    // merge all the tags and remove duplicates.
    if (!empty($customized_tags) && !empty($default_tags)) {
      $tags = array_keys(array_flip($customized_tags) + array_flip($default_tags));
    } else if (empty($customized_tags)) {
      $tags = $default_tags;
    } else if (empty($default_tags)) {
      $tags = $customized_tags;
    }
    return $tags;
  }

  public function steem_post_operation_params()
  {
    $params = array();
    $params['access_token'] = array(
      'required'       => true,
      'default'       => '',
      'description'        => __('User access token for login'),
      'type'               => 'string',
    );
    $params['post_id'] = array(
      'required'       => true,
      'default'       => 0,
      'description'        => __('Unique identifier of post'),
      'type'               => 'integer',
    );
    return $params;
  }
}
