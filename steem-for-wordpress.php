<?php

/**
 * Plugin Name: Steem for Wordpress
 * Version: 1.0.0
 * Plugin URI: http://github.com/steem-aksai/steem4wp
 * Description: Wordpress plugin for communicating with Steem.
 * Author: Steem Aksai
 * Author URI:
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: steem-for-wordpress
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Steem Aksai
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

// Load plugin class files.
require_once 'includes/class-steem-for-wordpress.php';
require_once 'includes/class-steem-for-wordpress-settings.php';

// Load plugin libraries.
require_once 'includes/lib/class-steem-for-wordpress-admin-api.php';
require_once 'includes/lib/class-steem-for-wordpress-post-type.php';
require_once 'includes/lib/class-steem-for-wordpress-taxonomy.php';
require_once 'includes/steem.php';
require_once 'includes/steem4wp.php';

/**
 * Returns the main instance of Steem_for_Wordpress to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Steem_for_Wordpress
 */
function steem_for_wordpress()
{
  $instance = Steem_for_Wordpress::instance(__FILE__, '1.0.0');

  if (is_null($instance->settings)) {
    $instance->settings = Steem_for_Wordpress_Settings::instance($instance);
  }

  return $instance;
}

steem_for_wordpress();

define('STEEM_REST_API_DIR', plugin_dir_path(__FILE__));

// after all plugins are loaded
add_action('plugins_loaded', 'steem4wp_plugins_loaded');
function steem4wp_plugins_loaded()
{
  include(STEEM_REST_API_DIR . 'includes/router.php');
}
