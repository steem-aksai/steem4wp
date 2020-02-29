<?php

if ( !defined( 'ABSPATH' ) ) exit;


include(STEEM_REST_API_DIR.'includes/auth.php');
include(STEEM_REST_API_DIR.'includes/post.php');
include(STEEM_REST_API_DIR.'includes/comment.php');
include(STEEM_REST_API_DIR.'includes/vote.php');
include(STEEM_REST_API_DIR.'includes/user.php');

add_action( 'rest_api_init', function () {
  $controls = array();
  $controls[] = new WP_Steem_REST_Post_Router();
  $controls[] = new WP_Steem_REST_Comment_Router();
  $controls[] = new WP_Steem_REST_Vote_Router();
  $controls[] = new WP_Steem_REST_User_Router();
  foreach ( $controls as $control ) {
    $control->register_routes();
  }
});
