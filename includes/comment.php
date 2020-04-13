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
class WP_Steem_REST_Comment_Router extends WP_REST_Controller
{

  /**
   * Instance of a post meta fields object.
   *
   * @since 4.7.0
   * @var WP_REST_Comment_Meta_Fields
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
  protected $rest_base = 'comments';

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

    $this->meta = new WP_REST_Comment_Meta_Fields();
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
        'callback'            => array($this, 'create_comments'),
        'permission_callback' => array($this, 'steem_comments_ops_permissions_check'),
        'args'                => $this->steem_comment_operation_params(),
      ),
    ));

    // register_rest_route( $this->namespace, '/' . $this->rest_base, array(
    //   array(
    //     'methods'             => WP_REST_Server::EDITABLE,
    //     'callback'            => array( $this, 'update_comments' ),
    //     'permission_callback' => array( $this, 'steem_comments_ops_permissions_check' ),
    //     'args'                => $this->steem_comment_operation_params(),
    //   )
    // ) );

    register_rest_route($this->namespace, '/' . $this->rest_base, array(
      array(
        'methods'             => WP_REST_Server::DELETABLE,
        'callback'            => array($this, 'delete_comments'),
        'permission_callback' => array($this, 'steem_comments_ops_permissions_check'),
        'args'                => $this->steem_comment_operation_params(),
      ),
    ));
  }

  public function steem_comments_ops_permissions_check($request)
  {

    if (empty($request['access_token'])) {
      return new WP_Error('access_token', __('User access token incorrect.'), array('status' => 400));
    }

    if (empty($request['comment_id'])) {
      return new WP_Error('comment_id', __('Comment ID cannot be empty.'), array('status' => 400));
    }

    return true;
  }

  public function create_comments($request)
  {

    $args = $request->get_params();
    $access_token = base64_decode($args['access_token']);
    $users = Steem_Auth::login($access_token);
    if (!$users) {
      return new WP_Error('error', 'User cannot publish content to Steem without authentication', array('status' => 400));
    }
    $user_id = $users->ID;
    $user = get_user_by('ID', $user_id);

    $comment_id = $args['comment_id'];
    $comment = get_comment($comment_id);
    if (empty($comment)) {
      return new WP_Error('error', '评论不存在, 请检查评论 ID 是否正确', array('status' => 400));
    }

    $post_id = $comment->comment_post_ID;
    if (!empty($comment->comment_post_ID)) {
      $post = get_post((int) $comment->comment_post_ID);
      if (empty($post)) {
        return new WP_Error('error', '评论的文章不存在, 请检查评论文章是否存在', array('status' => 400));
      }
    }

    if (empty($comment->comment_parent)) {
      $parent_author = get_post_meta($post_id, 'steem_author', true);
      $parent_permlink = get_post_meta($post_id, 'steem_permlink', true);
    } else {
      $parent_comment_id = $comment->comment_parent;
      $parent_comment = get_comment($comment->comment_parent);
      if (empty($parent_comment)) {
        return new WP_Error('error', '上级评论不存在, 检查上级评论是否存在', array('status' => 400));
      }
      $parent_author = get_comment_meta($parent_comment_id, 'steem_author', true);
      $parent_permlink = get_comment_meta($parent_comment_id, 'steem_permlink', true);
    }

    // write_log("parent_author: $parent_author");
    // write_log("parent_permlink: $parent_permlink");

    if (!$this->steem) {
      $this->steem = new Steem();
    }
    if ($this->steem && !empty($parent_author) && !empty($parent_permlink)) {
      if (empty($comment->comment_type)) { // 评论
        $tx = $this->steem->replyToPost($parent_author, $parent_permlink, $user->user_login, $comment->comment_content);
        if (!empty($tx) && array_key_exists('operations', $tx) && !array_key_exists('trace', $tx)) {
          $operation = $tx['operations'][0][1];
          update_comment_meta($comment_id, 'steem_author', $operation['author']);
          update_comment_meta($comment_id, 'steem_permlink', $operation['permlink']);
          write_log("createComment Succeeded");
          write_log($operation);
          write_log("----------");

          $this->create_2nd_comments($operation['author'], $operation['permlink'], $parent_author, $parent_permlink, $comment->comment_content);

          $result["status"] = 200;
          $result["code"] = "success";
          $result["message"] = 'Comment created on Steem sueccessfully';
          $response  = rest_ensure_response($result);
          return $response;
        } else {
          write_log("createComment Failed");
          write_log($tx);
          write_log("----------");
        }
      }
    }

    $result["status"] = 500;
    $result["code"] = "success";
    $result["message"] = "Failed to create comment on Steem";
    $response  = rest_ensure_response($result);
    return $response;
  }


  protected function create_2nd_comments($author, $permlink, $parentAuthor, $parentPermlink, $body)
  {
    try {
      $node = get_option("steem_2nd_api_node_url");
      if (!empty($node)) {
        $second_steem = new Steem($node);
        if ($second_steem) {
          write_log("createComment 2nd: @{$author}/{$permlink}");
          $tx = $second_steem->_comment($parentAuthor, $parentPermlink, $author, $permlink, "", $body);
          return $tx;
        } else {
          return null;
        }
      }
    } catch (\Exception $e) {
      write_log("failed to create comment @{$author}/{$permlink} on second Steem");
      return $e;
    }
  }


  public function delete_comments($request)
  {
    $access_token = base64_decode($request['access_token']);
    $users = Steem_Auth::login($access_token);
    if (!$users) {
      return new WP_Error('error', 'User hasn\'t logged in, cannot delete post', array('status' => 400));
    }
    $user_id = $users->ID;
    $user = get_user_by('ID', $user_id);

    $comment_id = $request['comment_id'];
    $comment = get_comment($comment_id);
    if (empty($comment)) {
      return new WP_Error('error', '评论不存在, 请检查评论 ID 是否正确', array('status' => 400));
    }

    $post_id = $comment->comment_post_ID;
    if (!empty($post_id)) {
      $post = get_post((int) $post_id);
      if (empty($post)) {
        return new WP_Error('error', '评论的文章不存在, 请检查评论文章是否存在', array('status' => 400));
      }
    }

    if (($user_id != $comment->user_id) && (in_array('administrator', $user->roles) || in_array('superadmin', $user->roles) || in_array('editor', $user->roles))) {
      return new WP_Error('error', '你没有权限删除评论', array('status' => 400));
    }

    if (!$this->steem) {
      $this->steem = new Steem();
    }
    if ($this->steem) {
      $steem_author = get_comment_meta($comment_id, 'steem_author', true);
      $steem_permlink = get_comment_meta($comment_id, 'steem_permlink', true);
      if (empty($comment->comment_type)) { // 评论
        // write_log("delete @$steem_author/$steem_permlink");
        if (!empty($steem_author) && !empty($steem_permlink)) {
          $tx = $this->steem->deletePost($steem_author, $steem_permlink);
          // write_log("deletion transaction");
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
    }

    $result["status"] = 500;
    $result["code"] = "success";
    $result["message"] = "Deletion falied";
    $response  = rest_ensure_response($result);
    return $response;
  }

  public function steem_comment_operation_params()
  {
    $params = array();
    $params['access_token'] = array(
      'required'       => true,
      'default'       => '',
      'description'        => __('User access token for login'),
      'type'               => 'string',
    );
    $params['comment_id'] = array(
      'required'       => true,
      'default'       => 0,
      'description'        => __('Unique identifier of comment'),
      'type'               => 'integer',
    );
    return $params;
  }
}
