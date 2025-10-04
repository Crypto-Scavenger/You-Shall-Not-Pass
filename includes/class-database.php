<?php
/**
 * Database operations for You Shall Not Pass
 *
 * @package YouShallNotPass
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YSNP_Database {
	
	private $table_name;
	private $cache_group = 'ysnp_settings';
	
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'ysnp_settings';
	}
	
	public static function activate() {
		$instance = new self();
		$instance->create_table();
		$instance->insert_defaults();
	}
	
	private function create_table() {
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = $wpdb->prepare(
			'CREATE TABLE IF NOT EXISTS %i (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				setting_key varchar(191) NOT NULL,
				setting_value longtext,
				PRIMARY KEY (id),
				UNIQUE KEY setting_key (setting_key)
			) %s',
			$this->table_name,
			$charset_collate
		);
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
	
	private function insert_defaults() {
		$defaults = array(
			'enabled'                => '1',
			'page_title'             => 'Access Restricted',
			'page_heading'           => 'You Shall Not Pass',
			'page_message'           => 'This content is restricted. Please log in to access.',
			'show_logo'              => '1',
			'logo_text'              => 'RESTRICTED AREA',
			'form_username_label'    => 'Username',
			'form_password_label'    => 'Password',
			'form_button_text'       => 'Enter',
			'form_remember_label'    => 'Remember Me',
			'show_remember'          => '1',
			'show_lost_password'     => '1',
			'lost_password_text'     => 'Lost your password?',
			'background_color'       => '#262626',
			'text_color'             => '#ffffff',
			'accent_color'           => '#d11c1c',
			'form_bg_color'          => 'rgba(38, 38, 38, 0.9)',
			'form_border_color'      => '#d11c1c',
			'input_bg_color'         => 'rgba(0, 0, 0, 0.5)',
			'input_text_color'       => '#ffffff',
			'input_border_color'     => 'rgba(209, 28, 28, 0.3)',
			'button_bg_color'        => '#d11c1c',
			'button_text_color'      => '#ffffff',
			'button_hover_color'     => '#ff1c1c',
			'custom_css'             => '',
			'cleanup_on_uninstall'   => '0',
		);
		
		foreach ( $defaults as $key => $value ) {
			$existing = $this->get_setting( $key );
			if ( false === $existing ) {
				$this->save_setting( $key, $value );
			}
		}
	}
	
	public function get_setting( $key, $default = false ) {
		$cache_key = 'setting_' . $key;
		$cached    = wp_cache_get( $cache_key, $this->cache_group );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		global $wpdb;
		
		$value = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT setting_value FROM %i WHERE setting_key = %s',
				$this->table_name,
				$key
			)
		);
		
		if ( null === $value ) {
			return $default;
		}
		
		wp_cache_set( $cache_key, $value, $this->cache_group );
		
		return $value;
	}
	
	public function save_setting( $key, $value ) {
		global $wpdb;
		
		$result = $wpdb->replace(
			$this->table_name,
			array(
				'setting_key'   => $key,
				'setting_value' => $value,
			),
			array( '%s', '%s' )
		);
		
		if ( false === $result ) {
			return new WP_Error( 'db_error', __( 'Failed to save setting', 'you-shall-not-pass' ) );
		}
		
		$cache_key = 'setting_' . $key;
		wp_cache_delete( $cache_key, $this->cache_group );
		
		return true;
	}
	
	public function get_all_settings() {
		global $wpdb;
		
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT setting_key, setting_value FROM %i',
				$this->table_name
			),
			ARRAY_A
		);
		
		if ( null === $results ) {
			return array();
		}
		
		$settings = array();
		foreach ( $results as $row ) {
			$settings[ $row['setting_key'] ] = $row['setting_value'];
		}
		
		return $settings;
	}
	
	public function delete_all_data() {
		global $wpdb;
		
		wp_cache_flush_group( $this->cache_group );
		
		$wpdb->query(
			$wpdb->prepare( 'DROP TABLE IF EXISTS %i', $this->table_name )
		);
	}
}
