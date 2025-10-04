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
	
	private $cache_group = 'ysnp_settings';
	private $table_verified = null;
	
	/**
	 * Get table name
	 *
	 * @return string
	 */
	private function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'ysnp_settings';
	}
	
	/**
	 * Activation hook
	 */
	public static function activate() {
		$instance = new self();
		$instance->create_table();
		$instance->insert_defaults();
	}
	
	/**
	 * Ensure table exists before operations
	 *
	 * @return bool
	 */
	private function ensure_table_exists() {
		if ( true === $this->table_verified ) {
			return true;
		}
		
		global $wpdb;
		$table_name = $this->get_table_name();
		
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table_name
		) );
		
		if ( $table_name === $table_exists ) {
			$this->table_verified = true;
			return true;
		}
		
		$this->create_table();
		
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table_name
		) );
		
		if ( $table_name === $table_exists ) {
			$this->table_verified = true;
			$this->insert_defaults();
			return true;
		}
		
		return false;
	}
	
	/**
	 * Create database table
	 */
	private function create_table() {
		global $wpdb;
		
		$table_name = $this->get_table_name();
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = $wpdb->prepare(
			'CREATE TABLE IF NOT EXISTS %i (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				setting_key varchar(191) NOT NULL,
				setting_value longtext,
				PRIMARY KEY (id),
				UNIQUE KEY setting_key (setting_key)
			)',
			$table_name
		) . ' ' . $charset_collate;
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
	
	/**
	 * Insert default settings
	 */
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
	
	/**
	 * Get a single setting
	 *
	 * @param string $key Setting key
	 * @param mixed $default Default value
	 * @return mixed
	 */
	public function get_setting( $key, $default = false ) {
		if ( ! $this->ensure_table_exists() ) {
			return $default;
		}
		
		$cache_key = 'setting_' . $key;
		$cached = wp_cache_get( $cache_key, $this->cache_group );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		global $wpdb;
		$table_name = $this->get_table_name();
		
		$value = $wpdb->get_var( $wpdb->prepare(
			'SELECT setting_value FROM %i WHERE setting_key = %s',
			$table_name,
			$key
		) );
		
		if ( null === $value ) {
			wp_cache_set( $cache_key, $default, $this->cache_group, 3600 );
			return $default;
		}
		
		wp_cache_set( $cache_key, $value, $this->cache_group, 3600 );
		return $value;
	}
	
	/**
	 * Save a setting
	 *
	 * @param string $key Setting key
	 * @param mixed $value Setting value
	 * @return bool
	 */
	public function save_setting( $key, $value ) {
		if ( ! $this->ensure_table_exists() ) {
			return false;
		}
		
		global $wpdb;
		$table_name = $this->get_table_name();
		
		$existing = $wpdb->get_var( $wpdb->prepare(
			'SELECT id FROM %i WHERE setting_key = %s',
			$table_name,
			$key
		) );
		
		if ( $existing ) {
			$result = $wpdb->update(
				$table_name,
				array( 'setting_value' => $value ),
				array( 'setting_key' => $key ),
				array( '%s' ),
				array( '%s' )
			);
		} else {
			$result = $wpdb->insert(
				$table_name,
				array(
					'setting_key'   => $key,
					'setting_value' => $value,
				),
				array( '%s', '%s' )
			);
		}
		
		$cache_key = 'setting_' . $key;
		wp_cache_delete( $cache_key, $this->cache_group );
		wp_cache_delete( 'all_settings', $this->cache_group );
		
		return false !== $result;
	}
	
	/**
	 * Get all settings
	 *
	 * @return array
	 */
	public function get_all_settings() {
		if ( ! $this->ensure_table_exists() ) {
			return array();
		}
		
		$cached = wp_cache_get( 'all_settings', $this->cache_group );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		global $wpdb;
		$table_name = $this->get_table_name();
		
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT setting_key, setting_value FROM %i',
				$table_name
			),
			ARRAY_A
		);
		
		$settings = array();
		
		if ( $results ) {
			foreach ( $results as $row ) {
				$settings[ $row['setting_key'] ] = $row['setting_value'];
			}
		}
		
		wp_cache_set( 'all_settings', $settings, $this->cache_group, 3600 );
		
		return $settings;
	}
	
	/**
	 * Delete a setting
	 *
	 * @param string $key Setting key
	 * @return bool
	 */
	public function delete_setting( $key ) {
		if ( ! $this->ensure_table_exists() ) {
			return false;
		}
		
		global $wpdb;
		$table_name = $this->get_table_name();
		
		$result = $wpdb->delete(
			$table_name,
			array( 'setting_key' => $key ),
			array( '%s' )
		);
		
		$cache_key = 'setting_' . $key;
		wp_cache_delete( $cache_key, $this->cache_group );
		wp_cache_delete( 'all_settings', $this->cache_group );
		
		return false !== $result;
	}
}
