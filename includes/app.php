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
class WP_Steem_REST_App_Router extends WP_REST_Controller {

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
	protected $rest_base = 'app';

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

		register_rest_route( $this->namespace, '/'.$this->rest_base, array(
			array(
				'methods'             	=> WP_REST_Server::READABLE,
				'callback'            	=> array( $this, 'app_settings' ),
				'permission_callback' 	=> array( $this, 'wp_app_permissions_check' ),
				'args'                	=> $this->wp_app_collection_params(),
			)
		));

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
	public function wp_app_permissions_check( $request ) {
		return true;
	}

	/**
	 * Retrieves the query params for the posts collection.
	 *
	 * @since 4.7.0
	 *
	 * @return array Collection parameters.
	 */
	public function wp_app_collection_params() {
		$params = array();
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

	public function app_settings( $request ) {
		$settigns = [
			"app_account" => get_option("steem_dapp_account"),
			"default_tags" => get_option("steem_dapp_default_tags")
		];
		$response = rest_ensure_response( $settigns );
		return $response;
	}

}

