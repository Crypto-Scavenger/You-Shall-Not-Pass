<?php
/**
 * Uninstall script for You Shall Not Pass
 *
 * @package YouShallNotPass
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$table_name = $wpdb->prefix . 'ysnp_settings';

$cleanup = $wpdb->get_var(
	$wpdb->prepare(
		'SELECT setting_value FROM %i WHERE setting_key = %s',
		$table_name,
		'cleanup_on_uninstall'
	)
);

if ( '1' === $cleanup ) {
	$wpdb->query(
		$wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name )
	);
}
