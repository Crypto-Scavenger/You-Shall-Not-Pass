<?php
/**
 * Plugin Name: You Shall Not Pass
 * Description: Content restriction plugin - displays only a login form page to non-logged-in users with customizable design
 * Version: 1.0.0
 * Requires at least: 6.8
 * Requires PHP: 7.4
 * Text Domain: you-shall-not-pass
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'YSNP_VERSION', '1.0.0' );
define( 'YSNP_DIR', plugin_dir_path( __FILE__ ) );
define( 'YSNP_URL', plugin_dir_url( __FILE__ ) );

require_once YSNP_DIR . 'includes/class-database.php';
require_once YSNP_DIR . 'includes/class-core.php';
require_once YSNP_DIR . 'includes/class-admin.php';

function ysnp_init() {
	$database = new YSNP_Database();
	$core     = new YSNP_Core( $database );
	
	if ( is_admin() ) {
		$admin = new YSNP_Admin( $database );
	}
}
add_action( 'plugins_loaded', 'ysnp_init' );

register_activation_hook( __FILE__, array( 'YSNP_Database', 'activate' ) );
