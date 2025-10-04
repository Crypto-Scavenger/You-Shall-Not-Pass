<?php
/**
 * SALT Key Management for Add Some Solt
 *
 * @package AddSomeSolt
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASS_Salt_Manager {

	private $salt_keys = array(
		'AUTH_KEY',
		'SECURE_AUTH_KEY',
		'LOGGED_IN_KEY',
		'NONCE_KEY',
		'AUTH_SALT',
		'SECURE_AUTH_SALT',
		'LOGGED_IN_SALT',
		'NONCE_SALT',
	);

	public function get_current_keys() {
		$keys = array();

		foreach ( $this->salt_keys as $key ) {
			if ( defined( $key ) ) {
				$keys[ $key ] = constant( $key );
			} else {
				$keys[ $key ] = '';
			}
		}

		return $keys;
	}

	public function generate_new_keys() {
		$url = 'https://api.wordpress.org/secret-key/1.1/salt/';
		$response = wp_remote_get( $url, array(
			'timeout' => 15,
			'sslverify' => true,
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', __( 'Failed to fetch new keys from WordPress API.', 'add-some-solt' ) );
		}

		$body = wp_remote_retrieve_body( $response );
		
		if ( empty( $body ) ) {
			return new WP_Error( 'empty_response', __( 'Received empty response from WordPress API.', 'add-some-solt' ) );
		}

		$keys = array();
		$lines = explode( "\n", $body );

		foreach ( $lines as $line ) {
			if ( preg_match( "/define\s*\(\s*'([^']+)'\s*,\s*'([^']+)'\s*\);/", $line, $matches ) ) {
				$key_name = $matches[1];
				$key_value = $matches[2];
				
				if ( in_array( $key_name, $this->salt_keys, true ) ) {
					$keys[ $key_name ] = $key_value;
				}
			}
		}

		if ( count( $keys ) !== count( $this->salt_keys ) ) {
			return new WP_Error( 'incomplete_keys', __( 'Failed to generate all required keys.', 'add-some-solt' ) );
		}

		return $keys;
	}

	public function update_wp_config( $new_keys ) {
		$config_path = $this->get_config_path();

		if ( ! $config_path ) {
			return new WP_Error( 'config_not_found', __( 'wp-config.php file not found.', 'add-some-solt' ) );
		}

		if ( ! is_writable( $config_path ) ) {
			return new WP_Error( 'not_writable', __( 'wp-config.php is not writable. Check file permissions.', 'add-some-solt' ) );
		}

		$config_content = file_get_contents( $config_path );

		if ( false === $config_content ) {
			return new WP_Error( 'read_error', __( 'Failed to read wp-config.php.', 'add-some-solt' ) );
		}

		$backup_path = dirname( $config_path ) . '/wp-config-backup-' . time() . '.php';
		$backup_created = file_put_contents( $backup_path, $config_content );

		if ( false === $backup_created ) {
			return new WP_Error( 'backup_failed', __( 'Failed to create backup of wp-config.php.', 'add-some-solt' ) );
		}

		foreach ( $new_keys as $key_name => $key_value ) {
			$pattern = "/define\s*\(\s*['\"]" . preg_quote( $key_name, '/' ) . "['\"]\s*,\s*['\"][^'\"]*['\"]\s*\);/";
			$replacement = "define( '" . $key_name . "', '" . addslashes( $key_value ) . "' );";
			
			$config_content = preg_replace( $pattern, $replacement, $config_content );
		}

		$write_result = file_put_contents( $config_path, $config_content );

		if ( false === $write_result ) {
			file_put_contents( $config_path, file_get_contents( $backup_path ) );
			return new WP_Error( 'write_error', __( 'Failed to update wp-config.php. Backup restored.', 'add-some-solt' ) );
		}

		if ( file_exists( $backup_path ) ) {
			wp_delete_file( $backup_path );
		}

		return true;
	}

	private function get_config_path() {
		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
			return ABSPATH . 'wp-config.php';
		}

		if ( file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
			return dirname( ABSPATH ) . '/wp-config.php';
		}

		return false;
	}

	public function check_config_writable() {
		$config_path = $this->get_config_path();

		if ( ! $config_path ) {
			return array(
				'writable' => false,
				'message' => __( 'wp-config.php file not found.', 'add-some-solt' ),
			);
		}

		if ( ! is_writable( $config_path ) ) {
			return array(
				'writable' => false,
				'message' => __( 'wp-config.php is not writable. File permissions need to be adjusted.', 'add-some-solt' ),
				'path' => $config_path,
			);
		}

		return array(
			'writable' => true,
			'message' => __( 'wp-config.php is writable and ready for updates.', 'add-some-solt' ),
			'path' => $config_path,
		);
	}

	public function replace_keys( $new_keys = null ) {
		if ( null === $new_keys ) {
			$new_keys = $this->generate_new_keys();
		}

		if ( is_wp_error( $new_keys ) ) {
			return $new_keys;
		}

		$result = $this->update_wp_config( $new_keys );

		return $result;
	}
}