<?php
/**
 * Plugin Name: Add Some Solt
 * Description: Manage and automate WordPress SALT key rotation with scheduled changes, notifications, and audit logging.
 * Version: 1.0.0
 * Text Domain: add-some-solt
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ASS_VERSION', '1.0.0' );
define( 'ASS_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASS_URL', plugin_dir_url( __FILE__ ) );
define( 'ASS_BASENAME', plugin_basename( __FILE__ ) );

require_once ASS_DIR . 'includes/class-database.php';
require_once ASS_DIR . 'includes/class-salt-manager.php';
require_once ASS_DIR . 'includes/class-scheduler.php';
require_once ASS_DIR . 'includes/class-core.php';
require_once ASS_DIR . 'includes/class-admin.php';

function ass_init() {
	$database = new ASS_Database();
	$salt_manager = new ASS_Salt_Manager();
	$scheduler = new ASS_Scheduler( $database, $salt_manager );
	$core = new ASS_Core( $database, $salt_manager, $scheduler );
	
	if ( is_admin() ) {
		new ASS_Admin( $core, $database, $salt_manager, $scheduler );
	}
}
add_action( 'plugins_loaded', 'ass_init' );

register_activation_hook( __FILE__, array( 'ASS_Database', 'activate' ) );