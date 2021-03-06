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
class WP_Steem_REST_User_Router extends WP_REST_Controller
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

    register_rest_route($this->namespace, '/' . $this->rest_base . '/login/wechat', array(
      array(
        'methods'               => WP_REST_Server::CREATABLE,
        'callback'              => array($this, 'wp_user_login_by_wechat'),
        'permission_callback'   => array($this, 'wp_user_wechat_login_permissions_check'),
        'args'                  => $this->wp_user_wechat_login_collection_params(),
      )
    ));

    register_rest_route($this->namespace, '/' . $this->rest_base . '/bind', array(
      array(
        'methods'               => WP_REST_Server::CREATABLE,
        'callback'              => array($this, 'wp_user_login_by_steem'),
        'permission_callback'   => array($this, 'wp_user_steem_login_permissions_check'),
        'args'                  => $this->wp_user_steem_login_collection_params(),
      )
    ));

    register_rest_route($this->namespace, '/' . $this->rest_base . '/exists', array(
      array(
        'methods'               => WP_REST_Server::READABLE,
        'callback'              => array($this, 'wp_user_exists_on_steem'),
        'permission_callback'   => array($this, 'wp_user_steem_query_permissions_check'),
        'args'                  => $this->wp_user_steem_exists_collection_params(),
      )
    ));

    register_rest_route($this->namespace, '/' . $this->rest_base . '/register', array(
      array(
        'methods'               => WP_REST_Server::CREATABLE,
        'callback'              => array($this, 'wp_user_register_steem_account'),
        'permission_callback'   => array($this, 'wp_user_steem_register_permissions_check'),
        'args'                  => $this->wp_user_steem_register_collection_params(),
      )
    ));

    register_rest_route($this->namespace, '/' . $this->rest_base . '/info', array(
      array(
        'methods'               => WP_REST_Server::READABLE,
        'callback'              => array($this, 'wp_user_get_steem_account_info'),
        'permission_callback'   => array($this, 'wp_user_steem_query_permissions_check'),
        'args'                  => $this->wp_user_get_steem_account_info_collection_params(),
      )
    ));

    register_rest_route($this->namespace, '/' . $this->rest_base . '/history', array(
      array(
        'methods'               => WP_REST_Server::READABLE,
        'callback'              => array($this, 'wp_user_get_steem_account_history'),
        'permission_callback'   => array($this, 'wp_user_steem_query_permissions_check'),
        'args'                  => $this->wp_user_get_steem_account_history_collection_params(),
      )
    ));

    register_rest_route($this->namespace, '/' . $this->rest_base . '/steem/id', array(
      array(
        'methods'               => WP_REST_Server::READABLE,
        'callback'              => array($this, 'wp_user_get_steem_id_by_wp_id'),
        'args'                  => $this->wp_user_get_steem_id_collection_params(),
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
  public function wp_user_steem_query_permissions_check($request)
  {

    $username = isset($request['username']) ? $request['username'] : "";

    if (empty($username)) {
      return new WP_Error('error', '缺少用户名', array('status' => 400));
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
  public function wp_user_steem_login_permissions_check($request)
  {

    $username = isset($request['username']) ? $request['username'] : "";
    $openId = isset($request['openId']) ? $request['openId'] : "";
    $token = isset($request['token']) ? $request['token'] : "";
    $expiredIn = isset($request['expired_in']) ? $request['expired_in'] : "";

    if (empty($username)) {
      return new WP_Error('error', '缺少用户名', array('status' => 400));
    }

    if (empty($openId)) {
      return new WP_Error('error', '缺少微信openId', array('status' => 400));
    }

    if (empty($token)) {
      return new WP_Error('error', '缺少登录token信息', array('status' => 400));
    }

    if (empty($expiredIn)) {
      return new WP_Error('error', '缺少登录过期时间信息', array('status' => 400));
    }

    return true;
  }


  /**
   * Checks if a given request has access to read posts.
   *
   * @since 4.7.0
   * @access public
   *
   * @param  WP_REST_Request $request Full details about the request.
   * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
   */
  public function wp_user_wechat_login_permissions_check($request)
  {

    $code = isset($request['code']) ? $request['code'] : "";
    $encryptedData = isset($request['encryptedData']) ? $request['encryptedData'] : "";
    $iv = isset($request['iv']) ? $request['iv'] : "";

    if (empty($encryptedData)) {
      return new WP_Error('error', '缺少用户信息的加密数据', array('status' => 400));
    }

    if (empty($code)) {
      return new WP_Error('error', '用户登录 code 参数错误', array('status' => 400));
    }

    if (empty($iv)) {
      return new WP_Error('error', '缺少加密算法的初始向量', array('status' => 400));
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
  public function wp_user_steem_register_permissions_check($request)
  {

    $username = isset($request['username']) ? $request['username'] : "";
    $openId = isset($request['openId']) ? $request['openId'] : "";
    $code = isset($request['code']) ? $request['code'] : "";
    $encryptedData = isset($request['encryptedData']) ? $request['encryptedData'] : "";
    $iv = isset($request['iv']) ? $request['iv'] : "";

    if (empty($username)) {
      return new WP_Error('error', '缺少用户名', array('status' => 400));
    }

    if (empty($openId)) {
      return new WP_Error('error', '缺少微信openId', array('status' => 400));
    }

    if (empty($encryptedData)) {
      return new WP_Error('error', '缺少用户信息的加密数据', array('status' => 400));
    }

    if (empty($code)) {
      return new WP_Error('error', '用户登录 code 参数错误', array('status' => 400));
    }

    if (empty($iv)) {
      return new WP_Error('error', '缺少加密算法的初始向量', array('status' => 400));
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
  public function wp_user_steem_exists_collection_params()
  {
    $params = array();
    $params['username'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "Steem用户名",
      'type'  =>   "string"
    );
    return $params;
  }

  /**
   * Retrieves the query params for querying steem account info
   *
   * @since 4.7.0
   *
   * @return array Collection parameters.
   */
  public function wp_user_get_steem_account_info_collection_params()
  {
    $params = array();
    $params['username'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "Steem用户名",
      'type'  =>   "string"
    );
    return $params;
  }

  /**
   * Retrieves the query params for querying steem account history
   *
   * @since 4.7.0
   *
   * @return array Collection parameters.
   */
  public function wp_user_get_steem_account_history_collection_params()
  {
    $params = array();
    $params['username'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "Steem用户名",
      'type'  =>   "string"
    );
    $params['start'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "查询的起始索引",
      'type'  =>   "int"
    );
    $params['limit'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "返回历史数据限额",
      'type'  =>   "int"
    );
    return $params;
  }

public function wp_user_get_steem_id_collection_params()
{
  $params = array(
    'wpid' => array(
      'required'  => true,
      'default'   => '',
      'description'=> 'wordpress_user_id',
      'type'      => 'string'
    )
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
  public function wp_user_steem_login_collection_params()
  {
    $params = array();
    $params['username'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "Steem用户名",
      'type'  =>   "string"
    );
    $params['openId'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "微信OpenID",
      'type'  =>   "string"
    );
    $params['token'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "steemconnect登录时的token",
      'type'  =>   "string"
    );
    $params['expired_in'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "steemconnect登录超时时间",
      'type'  =>   "double"
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
  public function wp_user_wechat_login_collection_params()
  {
    $params = array();
    $params['encryptedData'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "微信授权登录，包括敏感数据在内的完整用户信息的加密数据.",
      'type'  =>   "string"
    );
    $params['code'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "用户登录凭证（有效期五分钟）",
      'type'  =>   "string"
    );
    $params['iv'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "微信授权登录，加密算法的初始向量.",
      'type'  =>   "string"
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
  public function wp_user_steem_register_collection_params()
  {
    $params = array();
    $params['username'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "Steem用户名",
      'type'  =>   "string"
    );
    $params['openId'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "微信OpenID",
      'type'  =>   "string"
    );
    $params['encryptedData'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "微信授权登录，包括敏感数据在内的完整用户信息的加密数据.",
      'type'  =>   "string"
    );
    $params['code'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "用户登录凭证（有效期五分钟）",
      'type'  =>   "string"
    );
    $params['iv'] = array(
      'required' => true,
      'default'  => '',
      'description'  => "微信授权登录，加密算法的初始向量.",
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
  public function wp_user_exists_on_steem($request)
  {
    $params = $request->get_params();
    $steemId = $params['username'];
    if (!$this->steem && class_exists('Steem')) {
      $this->steem = new Steem();
    }
    $user_data = $this->steem->getAccount($steemId);
    if (!$user_data) {
      $data = [
        'exists' => false
      ];
    } else if (array_key_exists('name', $user_data) && !empty($user_data['name'])) {
      $data = [
        'exists' => true,
        'name' => $user_data['name']
      ];
    } else {
      return new WP_Error('error', '获取Steem用户数据出错', array('status' => 500, 'errcode' => $user_data));
    }
    $response = rest_ensure_response($data);
    return $response;
  }

  /**
   *
   * @since 4.7.0
   * @access public
   *
   * @param WP_REST_Request $request Full details about the request.
   * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
   */
  public function wp_user_get_steem_account_info($request)
  {
    $params = $request->get_params();
    $steemId = $params['username'];
    if (!$this->steem && class_exists('Steem')) {
      $this->steem = new Steem();
    }
    $user_data = $this->steem->getAccount($steemId);
    if (!$user_data) {
      return new WP_Error('error', 'Steem用户不存在', array('status' => 404, 'errcode' => $user_data));
    } else if (array_key_exists('name', $user_data) && !empty($user_data['name'])) {
      $data = $user_data;
    } else {
      return new WP_Error('error', '获取Steem用户数据出错', array('status' => 500, 'errcode' => $user_data));
    }
    $response = rest_ensure_response($data);
    return $response;
  }

  /**
   *
   * @since 4.7.0
   * @access public
   *
   * @param WP_REST_Request $request Full details about the request.
   * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
   */
  public function wp_user_get_steem_account_history($request)
  {
    $params = $request->get_params();
    $steemId = $params['username'];
    $start = $params['start'];
    $limit = $params['limit'];
    if (!$this->steem && class_exists('Steem')) {
      $this->steem = new Steem();
    }
    $history = $this->steem->getAccountHistory($steemId, $start, $limit);
    if (!empty($history) && count($history) > 0) {
      $response = rest_ensure_response($history);
      return $response;
    } else {
      return new WP_Error('error', '获取Steem用户数据出错', array('status' => 500, 'errcode' => $history));
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

  public function wp_user_login_by_steem($request)
  {
    date_default_timezone_set(get_option('timezone_string'));

    $params = $request->get_params();

    $steemId = $params['username'];
    $openId = $params['openId'];
    $access_token = $params['token'];
    $expired_in = $params['expired_in'];

    $access_token = base64_decode($access_token);
    SteemID::bind($openId, $steemId);

    return $this->login_by_steem($steemId, $openId, $access_token, $expired_in);
  }

  protected function login_by_steem($steemId, $openId, $access_token, $expired_in)
  {
    // date('Y-m-d H:i:s', time()+$expired_in);
    $expired_in = !empty($expired_in) ? $expired_in : 60480;
    $user_pass = wp_generate_password(16, false);
    $platform = 'wechat';
    $role = get_minapp_option('use_role');

    if (!$this->steem && class_exists('Steem')) {
      $this->steem = new Steem();
    }
    $user_data = $this->steem->getAccount($steemId);

    if (!$user_data) {
      return new WP_Error('error', '获取Steem用户数据为空', array('status' => 500, 'errcode' => $user_data));
    }
    $user_profile = $user_data['profile'];

    if (!username_exists($steemId)) {
      $userdata = array(
        'user_login'       => $steemId,
        'nickname'         => $user_profile['name'],
        'first_name'      => $user_profile['name'],
        'user_nicename'     => $steemId,
        'display_name'       => $user_profile['name'],
        'description'      => $user_profile['about'],
        'user_email'       => date('Ymdhms') . '@steem.com',
        'role'           => $role,
        'user_pass'       => $user_pass,
        'gender'        => null,
        'steemId'        => $steemId,
        'openid'        => $openId,
        'city'          => $user_profile['location'],
        'avatar'         => $user_profile['profile_image'],
        'province'        => null,
        'country'        => null,
        'language'        => 'zh_CN',
        'expire_in'        => $expired_in
      );
      $user_id = wp_insert_user($userdata);
      if (is_wp_error($user_id)) {
        return new WP_Error('error', '创建用户失败', array('status' => 404));
      }
      // add_user_meta( $user_id, 'unionid', $unionId );
      // add_user_meta( $user_id, 'session_key', $session['session_key'] );
      add_user_meta($user_id, 'openId', $openId);
      add_user_meta($user_id, 'session_key', $access_token);
      add_user_meta($user_id, 'platform', $platform);
      // $credits = (int)get_credit_option('member');
      // if($credits && is_numeric($credits)) {
      //   if($credits > 0) {
      //     $credits = abs($credits);
      //     $action = 'add';
      //     $description = '注册会员赠送积分：'.$credits;
      //   } else {
      //     $credits = abs($credits);
      //     $action = 'reduce';
      //     $description = '注册会员消耗积分：'.$credits;
      //   }
      //   $do_credits = mp_user_credit_trends_update( $user_id, $credits, $action, $description );
      // }
    } else {
      $user = get_user_by('login', $steemId);
      $userdata = array(
        'ID'              => $user->ID,
        'nickname'         => $user_profile['name'],
        'first_name'      => $user_profile['name'],
        'user_nicename'    => $user->user_login,
        'display_name'       => $user_profile['name'],
        'description'      => $user_profile['about'],
        'user_email'       => $user->user_email,
        'gender'        => '1',
        'steemId'          => $steemId ? $steemId : $user->user_login,
        'openid'        => $openId,
        'city'          => $user_profile['location'],
        'avatar'         => $user_profile['profile_image'],
        'province'        => null,
        'country'        => null,
        'language'        => 'zh_CN',
        'expire_in'        => $expired_in
      );
      $user_id = wp_update_user($userdata);
      if (is_wp_error($user_id)) {
        return new WP_Error('error', '更新用户信息失败', array('status' => 404));
      }
      // update_user_meta( $user_id, 'unionid', $unionId );
      // update_user_meta( $user_id, 'session_key', $session['session_key'] );
      update_user_meta($user_id, 'openId', $openId);
      update_user_meta($user_id, 'session_key', $access_token);
      update_user_meta($user_id, 'platform', $platform);
    }

    $current_user = get_user_by('ID', $user_id);
    $roles = (array) $current_user->roles;

    wp_set_current_user($user_id, $current_user->user_login);
    wp_set_auth_cookie($user_id, true);

    $user = [
      "user"  => [
        "userId"    => $user_id,
        "nickName"    => $userdata['nickname'],
        "steemId"    => $userdata['steemId'],
        "openid"    => $userdata['openid'],
        "avatarUrl"   => $userdata["avatar"],
        "gender"    => $userdata["gender"],
        "city"      => $userdata["city"],
        "province"    => $userdata["province"],
        "country"    => $userdata["country"],
        "language"    => $userdata["language"],
        "role"      => $roles[0],
        'platform'    => $platform,
        "description"  => $userdata['description']
      ],
      "access_token" => base64_encode($access_token),
      "expired_in" => $expired_in
    ];

    // if( class_exists('MP_Message') ) {
    //   $user["user"]["message"] = MP_Message::mp_message_nomark_count( $user_id );
    // }
    $response = rest_ensure_response($user);
    return $response;
  }

  /**
   *
   * @since 4.7.0
   * @access public
   *
   * @param WP_REST_Request $request Full details about the request.
   * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
   */

  public function wp_user_login_by_wechat($request)
  {
    $result = Steem_Auth::validateWeChatUser($request);
    if (is_wp_error($result)) {
      return $result;
    } else {
      $session = $result['session'];
    }

    $platform = "wechat";
    $openId = $session['openid'];
    $access_token = $session['session_key'];
    $user_query = new WP_User_Query(array('meta_key' => 'openId', 'meta_value' => $openId));
    $users = $user_query->get_results();
    if (!empty($users) && is_array($users)) {
      write_log("search users by openId {$openId}");
      write_log(count($users));
      write_log("------");
      $current_user = $users[0]; // FIXME: may have multiple accounts
    }

    // if find steemId locally, return user data
    if (!empty($current_user) && !empty($current_user->steemId)) {
      write_log('login_by_wechat from local database');
      return $this->login_by_steem($current_user->steemId, $openId, $access_token, null);
    } else {
      $steemId = SteemID::find($openId);
      write_log("login_by_wechat with SteemID {$steemId}");
      if (!empty($steemId)) {
        return $this->login_by_steem($steemId, $openId, $access_token, null);
      } else {
        write_log("login_by_wechat failed.");
        // $result = array( 'status' => 500, 'error' => 'no Steem users found', 'message' => '没有找到相关Steem用户' );
        // $response  = rest_ensure_response( $result );
        // return $response;
        return new WP_Error('error', '没有找到对应的Steem用户', array('status' => 404, 'openId' => $openId));
      }
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

  public function wp_user_register_steem_account($request)
  {
    $result = Steem_Auth::validateWeChatUser($request);
    if (is_wp_error($result)) {
      return $result;
    } else {
      $user_data = $result['user_data'];
      $session = $result['session'];
    }

    $profile = [
      "profile" => [
        "name" => $user_data['nickName'],
        "profile_image" => $user_data['avatarUrl']
      ]
    ];

    $params = $request->get_params();
    $steemId = $params['username'];
    $openId = $params['openId'];
    $access_token = $session['session_key'];

    $username = SteemID::new($openId, $steemId, json_encode($profile));
    if ($username) {
      write_log("register Steem account @{$steemId} succeeded.");
      $data = [
        'username' => $username,
        'token' => base64_encode($access_token),
        'expired_in' => 60480
      ];
      $response = rest_ensure_response($data);
      return $response;
    } else {
      write_log("register Steem account @{$steemId} failed.");
      return new WP_Error('error', "注册Steem账户 @{$steemId} 失败", array('status' => 500));
    }
  }

  public function wp_user_get_steem_id_by_wp_id($request){
    $wpid = isset($request['wpid']) ? (int)$request['wpid'] : 0;
    if ($wpid == '' || !is_numeric($wpid) || $wpid == 0){
			return new WP_Error( 'error', '无效用户 ID' , array( 'status' => 403 ) );
    }
    $wp_user = get_user_by('ID',$wpid);
    if (  !$wp_user ){
      return new WP_Error( 'error', '未发现WordPress用户', array( 'status' => 403) );
    }
    $data = array(
      'steemid'   => $wp_user->steemId
    );
    return rest_ensure_response($data);
  }
}
