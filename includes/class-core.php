<?php
/**
 * Core functionality for Add Some Solt
 *
 * @package AddSomeSolt
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASS_Core {

	private $database;
	private $salt_manager;
	private $scheduler;

	public function __construct( $database, $salt_manager, $scheduler ) {
		$this->database = $database;
		$this->salt_manager = $salt_manager;
		$this->scheduler = $scheduler;
	}

	public function manual_key_change() {
		$result = $this->salt_manager->replace_keys();

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->database->log_change( 'manual', 'Manual SALT key change by user' );

		$email_enabled = $this->database->get_setting( 'email_notifications', '0' );
		if ( '1' === $email_enabled ) {
			$this->send_manual_change_notification();
		}

		return true;
	}

	private function send_manual_change_notification() {
		$to = $this->database->get_setting( 'notification_email', get_option( 'admin_email' ) );
		$subject = __( 'SALT Keys Have Been Manually Changed', 'add-some-solt' );
		
		$user = wp_get_current_user();
		$user_name = $user->display_name;
		
		$message = sprintf(
			__( 'Your WordPress SALT keys have been manually changed by %s.', 'add-some-solt' ),
			$user_name
		);
		$message .= "\n\n" . __( 'All users have been logged out for security.', 'add-some-solt' );
		$message .= "\n\n" . __( 'Change Date: ', 'add-some-solt' ) . current_time( 'mysql' );
		$message .= "\n\n" . admin_url( 'admin.php?page=add-some-solt' );

		wp_mail( $to, $subject, $message );
	}

	public function get_database() {
		return $this->database;
	}

	public function get_salt_manager() {
		return $this->salt_manager;
	}

	public function get_scheduler() {
		return $this->scheduler;
	}
}