<?php
/**
 * Uninstall script for Add Some Solt
 *
 * @package AddSomeSolt
 * @since   1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$settings_table = $wpdb->prefix . 'ass_settings';

$cleanup = $wpdb->get_var( $wpdb->prepare(
	"SELECT setting_value FROM `{$settings_table}` WHERE setting_key = %s",
	'cleanup_on_uninstall'
) );

if ( '1' === $cleanup ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-database.php';
	
	$database = new ASS_Database();
	$database->delete_all_data();
	
	wp_clear_scheduled_hook( 'ass_scheduled_key_change' );
	wp_clear_scheduled_hook( 'ass_send_reminder' );
}