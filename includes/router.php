<?php

if ( !defined( 'ABSPATH' ) ) exit;


include(STEEM_REST_API_DIR.'includes/auth.php');
include(STEEM_REST_API_DIR.'includes/posts.php');

add_action( 'rest_api_init', function () {
  $controls = array();
  $controls[] = new WP_Steem_REST_Posts_Router();
  foreach ( $controls as $control ) {
    $control->register_routes();
  }
});
