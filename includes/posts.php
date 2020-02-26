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
class WP_Steem_REST_Posts_Router extends WP_REST_Controller {

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
	 * Constructor.
	 *
	 * @since 4.7.0
	 */
	public function __construct() {

		$this->meta = new WP_REST_Post_Meta_Fields( $this->post_type );

	}

	public function remove_excerpt_more() {
		return '';
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
				'callback'            => array( $this, 'create_posts' ),
				'permission_callback' => array( $this, 'steem_posts_ops_permissions_check' ),
				'args'                => $this->steem_post_operation_params(),
			),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_posts' ),
				'permission_callback' => array( $this, 'steem_posts_ops_permissions_check' ),
				'args'                => $this->steem_post_operation_params(),
			)
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_posts' ),
				'permission_callback' => array( $this, 'steem_posts_ops_permissions_check' ),
				'args'                => $this->steem_post_operation_params(),
			),
		) );

	}

	public function steem_posts_ops_permissions_check( $request ) {

		if ( empty( $request['access_token'] ) ) {
			return new WP_Error( 'access_token', __( 'User access token incorrect.' ), array( 'status' => 400 ) );
		}

		if ( empty( $request['post_id'] ) ) {
			return new WP_Error( 'post_id', __( 'Post ID cannot be empty.' ), array( 'status' => 400 ) );
		}

		return true;
	}

	public function create_posts( $request ) {

		$args = $request->get_params();
		$access_token = base64_decode($args['access_token']);
		$users = Steem_Auth::login( $access_token );
		if ( !$users ) {
			return new WP_Error( 'error', 'User cannot publish content to Steem without authentication' , array( 'status' => 400 ) );
		}
		$user_id = $users->ID;
		$user = get_user_by( 'ID', $user_id );

		$post_id = $request['post_id'];
		$post = get_post( $post_id );

    if (!$this->steem) {
      $this->steem = new Steem();
    }
    if ($this->steem) {
      $tx = $this->steem->createPost($user->user_login, $post->post_title, $post->post_content);
      // write_log("transaction");
      // write_log($tx);
      // write_log("----------");
      if (!empty($tx) && array_key_exists('operations', $tx) && !array_key_exists('trace', $tx)) {
        $operation = $tx['operations'][0][1];
        update_post_meta( $post_id, 'steem_author', $operation['author'] );
        update_post_meta( $post_id, 'steem_permlink', $operation['permlink'] );
        write_log("createPost");
        write_log($operation);
        write_log("----------");

        $result["status"] = 200;
        $result["code"] = "success";
        $result["message"] = 'Post created on Steem sueccessfully';
        $response  = rest_ensure_response( $result );
        return $response;
      }
    }

    $result["status"] = 500;
    $result["code"] = "success";
    $result["message"] = "Failed ot create post on Steem";
		$response  = rest_ensure_response( $result );
		return $response;

	}

	public function update_posts( $request ) {

		$access_token = base64_decode($request['access_token']);
		$users = Steem_Auth::login( $access_token );
		if ( !$users ) {
			return new WP_Error( 'error', 'User hasn\'t logged in, cannot edit post' , array( 'status' => 400 ) );
		}
		$user_id = $users->ID;
		$user = get_user_by( 'ID', $user_id );

		$post_id = $request['post_id'];
		$post = get_post( $post_id );
		$author_id = (int)$post->post_author;
		$post_status = isset($request['post_status'])?$request['post_status']:$post->post_status;

		$roles = ( array )$user->roles;
		if( ( $user_id != $author_id ) && ( in_array( 'administrator', $user->roles ) || in_array( 'superadmin', $user->roles ) || in_array( 'editor', $user->roles ) ) ) {
			return new WP_Error( 'error', 'You have no right to edit post', array( 'status' => 400 ) );
		}

		$postarr = array(
			'ID'           => $post_id,
      'post_status'  => $post_status,
		);

		if( $update ) {
			$result["code"] = "success";
			$result["message"] = "Update succeeded";
			$result["status"] = 200;
		} else {
			$result["code"] = "success";
			$result["message"] = "Update failed";
			$result["status"] = 500;
		}

		$response  = rest_ensure_response( $result );
		return $response;

	}

	public function delete_posts( $request ) {
    write_log("delete_posts 1");

		$access_token = base64_decode($request['access_token']);
		$users = Steem_Auth::login( $access_token );
		if ( !$users ) {
			return new WP_Error( 'error', 'User hasn\'t logged in, cannot delete post' , array( 'status' => 400 ) );
		}
		$user_id = $users->ID;
		$user = get_user_by( 'ID', $user_id );

		$post_id = $request["post_id"];
		$post = get_post( $post_id );
    $author_id = (int)$post->post_author;

    write_log("delete_posts 2");

		if( ( $user_id != $author_id ) && ( in_array( 'administrator', $user->roles ) || in_array( 'superadmin', $user->roles ) || in_array( 'editor', $user->roles ) ) ) {
			return new WP_Error( 'error', 'You have no permission to delete post', array( 'status' => 400 ) );
		}

    write_log("delete_posts 3");

    // $result["status"] = 500;
    // $result["code"] = "success";
    // $result["message"] = "Deletion falied";
		// $response  = rest_ensure_response( $result );
		// return $response;

    if (!$this->steem) {
      $this->steem = new Steem();
    }
    if ($this->steem) {
      $steem_author = get_post_meta( $post_id, 'steem_author', true );
      $steem_permlink = get_post_meta( $post_id, 'steem_permlink', true );

      sleep(30);
      write_log("delete_posts 4");
      if (!empty($steem_author) && !empty($steem_permlink)) {
        $tx = $this->steem->deletePost($steem_author, $steem_permlink);
        write_log("deletion transaction");
        write_log($steem_author);
        write_log($steem_permlink);
        write_log($tx);
        write_log("----------");
        if (!empty($tx) && array_key_exists('operations', $tx) && !array_key_exists('trace', $tx)) {
          $result["status"] = 200;
          $result["code"] = "success";
          $result["message"] = "Deletion succeeded";
          $response  = rest_ensure_response( $result );
          return $response;
        }
      }
    }

    $result["status"] = 500;
    $result["code"] = "success";
    $result["message"] = "Deletion falied";
		$response  = rest_ensure_response( $result );
		return $response;

	}

	public function steem_post_operation_params() {
		$params = array();
		$params['access_token'] = array(
			'required'			 => true,
			'default'			 => '',
			'description'        => __( 'User access token for login' ),
			'type'               => 'string',
		);
		$params['post_id'] = array(
			'required'			 => true,
			'default'			 => 0,
			'description'        => __( 'Unique identifier of post' ),
			'type'               => 'integer',
		);
		return $params;
	}

}

if (class_exists('WP_Async_Task')) {

  class Steem_Post_Async_Task extends WP_Async_Task {

    protected $action = 'steem_post';

    /**
     * @var $steem
     *
     * $steem will be the Steem object that interact with Steem blockchain
     */
    protected $steem;

    /**
     * Prepare data for the asynchronous request
     *
     * @throws Exception If for any reason the request should not happen
     *
     * @param array $data An array of data sent to the hook
     *
     * @return array
     */
    protected function prepare_data( $data ) {
      return [
        'operation' => $data[0],
        'user_id' => $data[1],
        'post_id' => $data[2]
      ];
    }

    /**
     * Run the async task action
     */
    protected function run_action() {
      $operation = $_POST['operation'];
      $user_id = $_POST['user_id'];
      $post_id = $_POST['post_id'];
      // $post = get_post( $post_id );
      // if ( $post ) {
        // Assuming $this->action is 'save_post'
        // do_action( "wp_async_$this->action", $post->ID, $post );
      // }
      switch ($operation) {
        case "create_post":
          $this->create_post($user_id, $post_id);
          break;
        case "delete_post":
          $this->delete_post($post_id);
          break;
      }
    }

    protected function create_post($user_id, $post_id) {
      $user = get_user_by( 'ID', $user_id );

      $post_id = $request['post_id'];
      $post = get_post( $post_id );

      if (!$this->steem) {
        $this->steem = new Steem();
      }
      if ($this->steem) {
        $tx = $this->steem->createPost($user->user_login, $post->post_title, $post->post_content);
        // write_log("transaction");
        // write_log($tx);
        // write_log("----------");
        if (!empty($tx) && array_key_exists('operations', $tx) && !array_key_exists('trace', $tx)) {
          $operation = $tx['operations'][0][1];
          update_post_meta( $post_id, 'steem_author', $operation['author'] );
          update_post_meta( $post_id, 'steem_permlink', $operation['permlink'] );
          write_log("createPost");
          write_log($operation);
          write_log("----------");

          // $result["status"] = 200;
          // $result["code"] = "success";
          // $result["message"] = 'Post created on Steem sueccessfully';
          // $response  = rest_ensure_response( $result );
          // return $response;
        }
      }
    }

    protected function delete_post($post_id) {
      if (!$this->steem) {
        $this->steem = new Steem();
      }
      if ($this->steem) {
        $steem_author = get_post_meta( $post_id, 'steem_author', true );
        $steem_permlink = get_post_meta( $post_id, 'steem_permlink', true );

        sleep(30);
        write_log("delete_posts 4");
        if (!empty($steem_author) && !empty($steem_permlink)) {
          $tx = $this->steem->deletePost($steem_author, $steem_permlink);
          write_log("deletion transaction");
          write_log($steem_author);
          write_log($steem_permlink);
          write_log($tx);
          write_log("----------");
          if (!empty($tx) && array_key_exists('operations', $tx) && !array_key_exists('trace', $tx)) {
            // $result["status"] = 200;
            // $result["code"] = "success";
            // $result["message"] = "Deletion succeeded";
            // $response  = rest_ensure_response( $result );
            // return $response;
          }
        }
      }
    }
  }
}


