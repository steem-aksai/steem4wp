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
class WP_Steem_REST_User_Router extends WP_REST_Controller {

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
	protected $rest_base = 'user';

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

		register_rest_route( $this->namespace, '/'.$this->rest_base.'/login', array(
			array(
				'methods'             	=> WP_REST_Server::CREATABLE,
				'callback'            	=> array( $this, 'wp_user_login_by_steem' ),
				'permission_callback' 	=> array( $this, 'wp_user_steem_login_permissions_check' ),
				'args'                	=> $this->wp_user_steem_login_collection_params(),
			)
		));

		register_rest_route( $this->namespace, '/'.$this->rest_base.'/exists', array(
			array(
				'methods'             	=> WP_REST_Server::READABLE,
				'callback'            	=> array( $this, 'wp_user_exists_on_steem' ),
				'permission_callback' 	=> array( $this, 'wp_user_steem_exists_permissions_check' ),
				'args'                	=> $this->wp_user_steem_exists_collection_params(),
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
	public function wp_user_steem_exists_permissions_check( $request ) {

		$username = isset($request['username']) ? $request['username'] : "";

		if( empty($username) ) {
			return new WP_Error( 'error', '缺少用户名', array( 'status' => 400 ) );
		}

		return true;

	}

	/**
	 * Checks if a given request has access to login as a user.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function wp_user_steem_login_permissions_check( $request ) {

		$username = isset($request['username']) ? $request['username'] : "";
		$token = isset($request['token']) ? $request['token'] : "";
		$expiredIn = isset($request['expired_in']) ? $request['expired_in'] : "";

		if( empty($username) ) {
			return new WP_Error( 'error', '缺少用户名', array( 'status' => 400 ) );
		}

		if( empty($token) ) {
			return new WP_Error( 'error', '缺少登录token信息', array( 'status' => 400 ) );
		}

		if( empty($expiredIn) ) {
			return new WP_Error( 'error', '缺少登录过期时间信息', array( 'status' => 400 ) );
		}

		return true;

	}


	/**
	 * Retrieves the query params for the posts collection.
	 *
	 * @since 4.7.0
	 *
	 * @return array Collection parameters.
	 */
	public function wp_user_steem_exists_collection_params() {
		$params = array();
		$params['username'] = array(
			'required' => true,
			'default'	=> '',
			'description'	=> "Steem用户名",
			'type'	=>	 "string"
		);
		return $params;
	}

	/**
	 * Retrieves the query params for the posts collection.
	 *
	 * @since 4.7.0
	 *
	 * @return array Collection parameters.
	 */
	public function wp_user_steem_login_collection_params() {
		$params = array();
		$params['username'] = array(
			'required' => true,
			'default'	=> '',
			'description'	=> "Steem用户名",
			'type'	=>	 "string"
		);
		$params['token'] = array(
			'required' => true,
			'default'	=> '',
			'description'	=> "steemconnect登录时的token",
			'type'	=>	 "string"
		);
		$params['expired_in'] = array(
			'required' => true,
			'default'	=> '',
			'description'	=> "steemconnect登录超时时间",
			'type'	=>	 "double"
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
	public function wp_user_exists_on_steem( $request ) {
		$params = $request->get_params();
		$steemId = $params['username'];
		if (!$this->steem && class_exists('Steem')) {
			$this->steem = new Steem();
		}
		$user_data = $this->steem->getAccount($steemId);
		if (!$user_data) {
			return new WP_Error( 'error', '获取Steem用户数据为空', array( 'status' => 500, 'errcode' => $user_data ) );
		} else {
			$user_profile = $user_data['profile'];
			$response = rest_ensure_response( $user_profile );
			return $response;
		}

	}

	/**
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */

	public function wp_user_login_by_steem( $request ) {
		date_default_timezone_set(get_option('timezone_string'));

		$appid 			= get_minapp_option('appid');
		$appsecret 		= get_minapp_option('secretkey');
		$role 			= get_minapp_option('use_role');

		$params = $request->get_params();

		$steemId = $params['username'];
		$access_token = $params['token'];
		$expired_in = $params['expired_in'];
		$expire_date = date('Y-m-d H:i:s', time()+72000); // date('Y-m-d H:i:s', time()+$expired_in);
		$user_pass = wp_generate_password(16, false);
		$platform = 'steem';

		if (!$this->steem && class_exists('Steem')) {
			$this->steem = new Steem();
		}
		$user_data = $this->steem->getAccount($steemId);

		if (!$user_data) {
			return new WP_Error( 'error', '获取Steem用户数据为空', array( 'status' => 500, 'errcode' => $user_data ) );
		}
		$user_profile = $user_data['profile'];

		if( !username_exists($steemId) ) {
			$userdata = array(
        'user_login' 			=> $steemId,
				'nickname' 				=> $user_profile['name'],
				'first_name'			=> $user_profile['name'],
				'user_nicename' 		=> $steemId,
				'display_name' 			=> $user_profile['name'],
				'description'			=> $user_profile['about'],
				'user_email' 			=> date('Ymdhms').'@steem.com',
				'role' 					=> $role,
				'user_pass' 			=> $user_pass,
				'gender'				=> null,
				'steemId'				=> $steemId,
				'city'					=> $user_profile['location'],
				'avatar' 				=> $user_profile['profile_image'],
				'province'				=> null,
				'country'				=> null,
				'language'				=> 'zh_CN',
				'expire_in'				=> $expire_date
      );
			$user_id = wp_insert_user( $userdata );
			if ( is_wp_error( $user_id ) ) {
				return new WP_Error( 'error', '创建用户失败', array( 'status' => 404 ) );
			}
			// add_user_meta( $user_id, 'unionid', $unionId );
			// add_user_meta( $user_id, 'session_key', $session['session_key'] );
			add_user_meta( $user_id, 'session_key', base64_decode($access_token) );
			add_user_meta( $user_id, 'platform', $platform);
			// $credits = (int)get_credit_option('member');
			// if($credits && is_numeric($credits)) {
			// 	if($credits > 0) {
			// 		$credits = abs($credits);
			// 		$action = 'add';
			// 		$description = '注册会员赠送积分：'.$credits;
			// 	} else {
			// 		$credits = abs($credits);
			// 		$action = 'reduce';
			// 		$description = '注册会员消耗积分：'.$credits;
			// 	}
			// 	$do_credits = mp_user_credit_trends_update( $user_id, $credits, $action, $description );
			// }
		} else {
			$user = get_user_by('login', $steemId );
			$userdata = array(
        'ID'            	=> $user->ID,
				'nickname' 				=> $user_profile['name'],
				'first_name'			=> $user_profile['name'],
				'user_nicename'		=> $user->user_login,
				'display_name' 			=> $user_profile['name'],
				'description'			=> $user_profile['about'],
				'user_email' 			=> $user->user_email,
				'gender'				=> null,
				'steemId'				  => $steemId ? $steemId : $user->user_login,
				'city'					=> $user_profile['location'],
				'avatar' 				=> $user_profile['profile_image'],
				'province'				=> null,
				'country'				=> null,
				'language'				=> 'zh_CN',
				'expire_in'				=> $expire_date
      );
			$user_id = wp_update_user($userdata);
			if(is_wp_error($user_id)) {
				return new WP_Error( 'error', '更新用户信息失败' , array( 'status' => 404 ) );
			}
			// update_user_meta( $user_id, 'unionid', $unionId );
			// update_user_meta( $user_id, 'session_key', $session['session_key'] );
			add_user_meta( $user_id, 'session_key', base64_decode($access_token) );
			update_user_meta($user_id, 'platform', $platform);
		}

		$current_user = get_user_by( 'ID', $user_id );
		$roles = ( array )$current_user->roles;

		wp_set_current_user( $user_id, $current_user->user_login );
		wp_set_auth_cookie( $user_id, true );

		$user = [
			"user"	=> [
				"userId"		=> $user_id,
				"nickName"		=> $userdata['nickname'],
				"steemId"		=> $userdata['steemId'],
				"avatarUrl" 	=> $userdata["avatar"],
				"gender"		=> $userdata["gender"],
				"city"			=> $userdata["city"],
				"province"		=> $userdata["province"],
				"country"		=> $userdata["country"],
				"language"		=> $userdata["language"],
				"role"			=> $roles[0],
				'platform'		=> $platform,
				"description"	=> $userdata['description']
			],
			"access_token" => $access_token,
			"expired_in" => $expire_date
		];

		// if( class_exists('MP_Message') ) {
		// 	$user["user"]["message"] = MP_Message::mp_message_nomark_count( $user_id );
		// }
		$response = rest_ensure_response( $user );
		return $response;
	}

}

