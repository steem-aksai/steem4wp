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
class WP_Steem_REST_Chain_Router extends WP_REST_Controller
{

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
  protected $rest_base = 'chain';

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
    register_rest_route($this->namespace, '/' . $this->rest_base . '/state', array(
      array(
        'methods'               => WP_REST_Server::READABLE,
        'callback'              => array($this, 'wp_chain_get_state'),
        'permission_callback'   => array($this, 'wp_chain_get_state_permissions_check'),
        'args'                  => $this->wp_chain_get_state_collection_params(),
      )
    ));
  }

  /**
   * Checks if a given request has access to check whether user exists.
   *
   * @since 4.7.0
   * @access public
   *
   * @param  WP_REST_Request $request Full details about the request.
   * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
   */
  public function wp_chain_get_state_permissions_check($request)
  {

    $path = isset($request['path']) ? $request['path'] : "";

    if (empty($path)) {
      return new WP_Error('error', '缺少路径', array('status' => 400));
    }

    return true;
  }

  /**
   * Retrieves the query params for querying steem account info
   *
   * @since 4.7.0
   *
   * @return array Collection parameters.
   */
  public function wp_chain_get_state_collection_params()
  {
    $params = array();
    $params['path'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "查询路径",
      'type'  =>   "string"
    );
    return $params;
  }

  /**
   *
   * @since 4.7.0
   * @access public
   *
   * @param WP_REST_Request $request Full details about the request.
   * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
   */
  public function wp_chain_get_state($request)
  {
    $params = $request->get_params();
    $path = $params['path'];
    if (!$this->steem && class_exists('Steem')) {
      $this->steem = new Steem();
    }
    $state = $this->steem->getState($path);
    if (empty($state)) {
      return new WP_Error('error', '找不到对应的路径', array('status' => 404, 'errcode ' => $state));
    } else if (array_key_exists('props', $state) && !empty($state['props'])) {
      return rest_ensure_response($state);
    } else {
      return new WP_Error('error', '获取Steem用户数据出错', array('status' => 500, 'errcode' => $state));
    }
  }
}
