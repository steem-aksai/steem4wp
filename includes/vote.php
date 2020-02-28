<?php
/**
 * REST API: WP_REST_Posts_Controller class
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.7.0
 */
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Core class to access posts via the REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Controller
 */
class WP_Steem_REST_Vote_Router extends WP_REST_Controller {

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
	protected $rest_base = 'votes';

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
	public function __construct() {

	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 4.7.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'vote' ),
				'permission_callback' => array( $this, 'steem_votes_ops_permissions_check' ),
				'args'                => $this->steem_vote_operation_params(),
			),
		) );

	}

	public function steem_votes_ops_permissions_check( $request ) {

		if ( empty( $request['access_token'] ) ) {
			return new WP_Error( 'access_token', __( 'User access token incorrect.' ), array( 'status' => 400 ) );
		}

		if ( empty( $request['id'] ) ) {
			return new WP_Error( 'id', __( 'ID cannot be empty.' ), array( 'status' => 400 ) );
		}

		if ( empty( $request['type'] ) ) {
			return new WP_Error( 'type', __( 'Content type cannot be empty.' ), array( 'status' => 400 ) );
		}

		if ( $request['weight'] < 0 ) {
			return new WP_Error( 'weight', __( 'Voting weight cannot be negative value.' ), array( 'status' => 400 ) );
		}

		return true;
	}

	public function vote( $request ) {

		$args = $request->get_params();
		$access_token = base64_decode($args['access_token']);
		$users = Steem_Auth::login( $access_token );
		if ( !$users ) {
			return new WP_Error( 'error', 'User cannot publish content to Steem without authentication' , array( 'status' => 400 ) );
		}
		$user_id = $users->ID;
		$user = get_user_by( 'ID', $user_id );

		$id = $args['id'];
		$weight = $args['weight'];

		if ($args['type'] == 'post') {
			$post = get_post( $id );
			if (empty($post)) {
				return new WP_Error( 'error', '评论的文章不存在, 请检查评论文章是否存在' , array( 'status' => 400 ) );
			}
      $author = get_post_meta( $id, 'steem_author', true );
      $permlink = get_post_meta( $id, 'steem_permlink', true );
		} else {
			$comment = get_comment( $id );
			if (empty( $comment)) {
				return new WP_Error('error', '评论不存在, 请检查评论 ID 是否正确', array( 'status' => 400 ) );
			}
			$author = get_comment_meta( $id, 'steem_author', true );
      $permlink = get_comment_meta( $id, 'steem_permlink', true );
		}

		// write_log("author: $author");
		// write_log("permlink: $permlink");

    if (!$this->steem) {
      $this->steem = new Steem();
		}
    if ($this->steem && !empty($author) && !empty($permlink)) {
			if ($weight > 0) {
				$tx = $this->steem->upvotePost($user->user_login, $author, $permlink, $weight);
			} else {
				$tx = $this->steem->unvotePost($user->user_login, $author, $permlink);
			}
			// write_log("transaction");
			// write_log($tx);
			// write_log("----------");
			if (!empty($tx) && array_key_exists('operations', $tx) && !array_key_exists('trace', $tx)) {
				$operation = $tx['operations'][0][1];
				// write_log("operation");
				// write_log($operation);
				// write_log("----------");
				$result["status"] = 200;
				$result["code"] = "success";
				$result["message"] = 'Vote on Steem sueccessfully';
				$response  = rest_ensure_response( $result );
				return $response;
			}
    }

    $result["status"] = 500;
    $result["code"] = "success";
    $result["message"] = "Failed to vote on Steem";
		$response  = rest_ensure_response( $result );
		return $response;

	}

	public function steem_vote_operation_params() {
		$params = array();
		$params['access_token'] = array(
			'required'			 => true,
			'default'			 => '',
			'description'        => __( 'User access token for login' ),
			'type'               => 'string',
		);
		$params['id'] = array(
			'required'			 => true,
			'default'			 => 0,
			'description'        => __( 'Unique identifier of post or comment' ),
			'type'               => 'integer',
		);
		$params['type'] = array(
			'required'			 => false,
			'default'			 => 'post',
			'description'        => __( 'The type of voting target' ),
			'type'               => 'string',
		);
		$params['weight'] = array(
			'required'			 => true,
			'default'			 => 0,
			'description'        => __( 'Voting weight' ),
			'type'               => 'integer',
		);
		return $params;
	}

}

