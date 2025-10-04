<?php
/**
 * Database operations for Add Some Solt
 *
 * @package AddSomeSolt
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASS_Database {

	private $table_verified = null;
	private $log_table_verified = null;
	private $settings_cache = null;
	private $cache_key = 'ass_settings_cache';
	private $cache_expiration = 43200;

	public static function activate() {
		$instance = new self();
		$instance->create_tables();
	}

	private function create_tables() {
		global $wpdb;
		
		$settings_table = $wpdb->prefix . 'ass_settings';
		$log_table = $wpdb->prefix . 'ass_change_log';
		$charset_collate = $wpdb->get_charset_collate();

		$sql_settings = "CREATE TABLE IF NOT EXISTS `{$settings_table}` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			setting_key varchar(191) NOT NULL,
			setting_value longtext,
			PRIMARY KEY (id),
			UNIQUE KEY setting_key (setting_key)
		) {$charset_collate}";

		$sql_log = "CREATE TABLE IF NOT EXISTS `{$log_table}` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			change_date datetime NOT NULL,
			changed_by bigint(20) unsigned,
			change_type varchar(50) NOT NULL,
			notes text,
			PRIMARY KEY (id),
			KEY change_date (change_date)
		) {$charset_collate}";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_settings );
		dbDelta( $sql_log );

		$defaults = array(
			'schedule_enabled' => '0',
			'schedule_frequency' => 'quarterly',
			'schedule_day' => '1',
			'schedule_time' => '03:00',
			'email_notifications' => '1',
			'notification_email' => get_option( 'admin_email' ),
			'reminder_enabled' => '0',
			'reminder_days' => '7',
			'cleanup_on_uninstall' => '1',
		);

		foreach ( $defaults as $key => $value ) {
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM `{$settings_table}` WHERE setting_key = %s",
				$key
			) );

			if ( ! $existing ) {
				$wpdb->insert(
					$settings_table,
					array(
						'setting_key' => $key,
						'setting_value' => $value,
					),
					array( '%s', '%s' )
				);
			}
		}
	}

	private function ensure_table_exists() {
		if ( true === $this->table_verified ) {
			return true;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'ass_settings';

		$table_exists = $wpdb->get_var( $wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table_name
		) );

		if ( $table_name === $table_exists ) {
			$this->table_verified = true;
			return true;
		}

		$this->create_tables();

		$table_exists = $wpdb->get_var( $wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table_name
		) );

		if ( $table_name === $table_exists ) {
			$this->table_verified = true;
			return true;
		}

		return false;
	}

	private function ensure_log_table_exists() {
		if ( true === $this->log_table_verified ) {
			return true;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'ass_change_log';

		$table_exists = $wpdb->get_var( $wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table_name
		) );

		if ( $table_name === $table_exists ) {
			$this->log_table_verified = true;
			return true;
		}

		$this->create_tables();

		$table_exists = $wpdb->get_var( $wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table_name
		) );

		if ( $table_name === $table_exists ) {
			$this->log_table_verified = true;
			return true;
		}

		return false;
	}

	public function get_setting( $key, $default = false ) {
		if ( ! $this->ensure_table_exists() ) {
			return $default;
		}

		$cache = get_transient( $this->cache_key );
		if ( false !== $cache && isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'ass_settings';

		$value = $wpdb->get_var( $wpdb->prepare(
			"SELECT setting_value FROM `{$table_name}` WHERE setting_key = %s",
			$key
		) );

		if ( null === $value ) {
			return $default;
		}

		if ( false === $cache ) {
			$cache = array();
		}
		$cache[ $key ] = $value;
		set_transient( $this->cache_key, $cache, $this->cache_expiration );

		return $value;
	}

	public function save_setting( $key, $value, $skip_cache_clear = false ) {
		if ( ! $this->ensure_table_exists() ) {
			return false;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'ass_settings';

		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM `{$table_name}` WHERE setting_key = %s",
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
					'setting_key' => $key,
					'setting_value' => $value,
				),
				array( '%s', '%s' )
			);
		}

		if ( false === $result ) {
			return false;
		}

		if ( ! $skip_cache_clear ) {
			delete_transient( $this->cache_key );
		}

		return true;
	}

	public function get_all_settings() {
		if ( null !== $this->settings_cache ) {
			return $this->settings_cache;
		}

		if ( ! $this->ensure_table_exists() ) {
			return array();
		}

		$cache = get_transient( $this->cache_key );
		if ( false !== $cache ) {
			$this->settings_cache = $cache;
			return $cache;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'ass_settings';

		$results = $wpdb->get_results(
			"SELECT setting_key, setting_value FROM `{$table_name}`",
			ARRAY_A
		);

		$settings = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $row ) {
				$settings[ $row['setting_key'] ] = $row['setting_value'];
			}
		}

		set_transient( $this->cache_key, $settings, $this->cache_expiration );
		$this->settings_cache = $settings;

		return $settings;
	}

	public function log_change( $change_type, $notes = '' ) {
		if ( ! $this->ensure_log_table_exists() ) {
			return false;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'ass_change_log';

		$user_id = get_current_user_id();
		if ( 0 === $user_id && defined( 'DOING_CRON' ) && DOING_CRON ) {
			$user_id = null;
		}

		$result = $wpdb->insert(
			$table_name,
			array(
				'change_date' => current_time( 'mysql' ),
				'changed_by' => $user_id,
				'change_type' => $change_type,
				'notes' => $notes,
			),
			array( '%s', '%d', '%s', '%s' )
		);

		return false !== $result;
	}

	public function get_change_log( $limit = 50 ) {
		if ( ! $this->ensure_log_table_exists() ) {
			return array();
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'ass_change_log';

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM `{$table_name}` ORDER BY change_date DESC LIMIT %d",
			$limit
		), ARRAY_A );

		if ( ! is_array( $results ) ) {
			return array();
		}

		return $results;
	}

	public function delete_all_data() {
		global $wpdb;
		
		$settings_table = $wpdb->prefix . 'ass_settings';
		$log_table = $wpdb->prefix . 'ass_change_log';

		$wpdb->query( "DROP TABLE IF EXISTS `{$settings_table}`" );
		$wpdb->query( "DROP TABLE IF EXISTS `{$log_table}`" );

		delete_transient( $this->cache_key );
	}
}