<?php
/**
 * Scheduler for Add Some Solt
 *
 * @package AddSomeSolt
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASS_Scheduler {

	private $database;
	private $salt_manager;

	public function __construct( $database, $salt_manager ) {
		$this->database = $database;
		$this->salt_manager = $salt_manager;

		add_action( 'ass_scheduled_key_change', array( $this, 'execute_scheduled_change' ) );
		add_action( 'ass_send_reminder', array( $this, 'send_reminder_email' ) );
	}

	public function schedule_key_change( $frequency, $day, $time ) {
		$this->clear_scheduled_changes();

		$next_run = $this->calculate_next_run( $frequency, $day, $time );

		if ( false === $next_run ) {
			return false;
		}

		$scheduled = wp_schedule_single_event( $next_run, 'ass_scheduled_key_change' );

		if ( false === $scheduled ) {
			return false;
		}

		$this->schedule_reminder();

		return $next_run;
	}

	private function calculate_next_run( $frequency, $day, $time ) {
		$time_parts = explode( ':', $time );
		if ( count( $time_parts ) !== 2 ) {
			return false;
		}

		$hour = intval( $time_parts[0] );
		$minute = intval( $time_parts[1] );

		$current_time = current_time( 'timestamp' );
		$target_time = strtotime( 'today ' . $hour . ':' . $minute . ':00' );

		switch ( $frequency ) {
			case 'daily':
				if ( $target_time <= $current_time ) {
					$target_time = strtotime( 'tomorrow ' . $hour . ':' . $minute . ':00' );
				}
				break;

			case 'weekly':
				$current_day = intval( gmdate( 'N', $current_time ) );
				$target_day = intval( $day );
				
				$days_ahead = $target_day - $current_day;
				
				if ( $days_ahead < 0 || ( $days_ahead === 0 && $target_time <= $current_time ) ) {
					$days_ahead += 7;
				}
				
				$target_time = strtotime( '+' . $days_ahead . ' days ' . $hour . ':' . $minute . ':00' );
				break;

			case 'monthly':
				$target_day = intval( $day );
				$current_day = intval( gmdate( 'j', $current_time ) );
				$current_month = gmdate( 'Y-m', $current_time );
				
				$target_time = strtotime( $current_month . '-' . str_pad( $target_day, 2, '0', STR_PAD_LEFT ) . ' ' . $hour . ':' . $minute . ':00' );
				
				if ( $target_time <= $current_time || false === $target_time ) {
					$next_month = gmdate( 'Y-m', strtotime( '+1 month', $current_time ) );
					$target_time = strtotime( $next_month . '-' . str_pad( $target_day, 2, '0', STR_PAD_LEFT ) . ' ' . $hour . ':' . $minute . ':00' );
				}
				break;

			case 'quarterly':
				$current_month = intval( gmdate( 'n', $current_time ) );
				$quarter_months = array( 1, 4, 7, 10 );
				
				$target_day = intval( $day );
				$next_quarter_month = null;
				
				foreach ( $quarter_months as $qm ) {
					$test_time = strtotime( gmdate( 'Y', $current_time ) . '-' . str_pad( $qm, 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $target_day, 2, '0', STR_PAD_LEFT ) . ' ' . $hour . ':' . $minute . ':00' );
					
					if ( false !== $test_time && $test_time > $current_time ) {
						$next_quarter_month = $qm;
						break;
					}
				}
				
				if ( null === $next_quarter_month ) {
					$next_year = intval( gmdate( 'Y', $current_time ) ) + 1;
					$target_time = strtotime( $next_year . '-01-' . str_pad( $target_day, 2, '0', STR_PAD_LEFT ) . ' ' . $hour . ':' . $minute . ':00' );
				} else {
					$target_time = strtotime( gmdate( 'Y', $current_time ) . '-' . str_pad( $next_quarter_month, 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $target_day, 2, '0', STR_PAD_LEFT ) . ' ' . $hour . ':' . $minute . ':00' );
				}
				break;

			case 'biannually':
				$target_day = intval( $day );
				$half_year_months = array( 1, 7 );
				$current_month = intval( gmdate( 'n', $current_time ) );
				
				$next_month = null;
				foreach ( $half_year_months as $hm ) {
					$test_time = strtotime( gmdate( 'Y', $current_time ) . '-' . str_pad( $hm, 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $target_day, 2, '0', STR_PAD_LEFT ) . ' ' . $hour . ':' . $minute . ':00' );
					
					if ( false !== $test_time && $test_time > $current_time ) {
						$next_month = $hm;
						break;
					}
				}
				
				if ( null === $next_month ) {
					$next_year = intval( gmdate( 'Y', $current_time ) ) + 1;
					$target_time = strtotime( $next_year . '-01-' . str_pad( $target_day, 2, '0', STR_PAD_LEFT ) . ' ' . $hour . ':' . $minute . ':00' );
				} else {
					$target_time = strtotime( gmdate( 'Y', $current_time ) . '-' . str_pad( $next_month, 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $target_day, 2, '0', STR_PAD_LEFT ) . ' ' . $hour . ':' . $minute . ':00' );
				}
				break;

			default:
				return false;
		}

		return $target_time;
	}

	public function execute_scheduled_change() {
		$result = $this->salt_manager->replace_keys();

		if ( is_wp_error( $result ) ) {
			$this->database->log_change( 'scheduled_failed', $result->get_error_message() );
			return;
		}

		$this->database->log_change( 'scheduled_automatic', 'Scheduled automatic SALT key change completed' );

		$email_enabled = $this->database->get_setting( 'email_notifications', '0' );
		if ( '1' === $email_enabled ) {
			$this->send_change_notification();
		}

		$schedule_enabled = $this->database->get_setting( 'schedule_enabled', '0' );
		if ( '1' === $schedule_enabled ) {
			$frequency = $this->database->get_setting( 'schedule_frequency', 'quarterly' );
			$day = $this->database->get_setting( 'schedule_day', '1' );
			$time = $this->database->get_setting( 'schedule_time', '03:00' );
			
			$this->schedule_key_change( $frequency, $day, $time );
		}
	}

	private function schedule_reminder() {
		wp_clear_scheduled_hook( 'ass_send_reminder' );

		$reminder_enabled = $this->database->get_setting( 'reminder_enabled', '0' );
		
		if ( '1' !== $reminder_enabled ) {
			return;
		}

		$next_change = wp_next_scheduled( 'ass_scheduled_key_change' );
		
		if ( false === $next_change ) {
			return;
		}

		$reminder_days = intval( $this->database->get_setting( 'reminder_days', '7' ) );
		$reminder_time = $next_change - ( $reminder_days * DAY_IN_SECONDS );

		if ( $reminder_time > current_time( 'timestamp' ) ) {
			wp_schedule_single_event( $reminder_time, 'ass_send_reminder' );
		}
	}

	public function send_reminder_email() {
		$to = $this->database->get_setting( 'notification_email', get_option( 'admin_email' ) );
		$subject = __( 'Reminder: Scheduled SALT Key Change', 'add-some-solt' );
		
		$next_change = wp_next_scheduled( 'ass_scheduled_key_change' );
		$change_date = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_change );
		
		$message = sprintf(
			__( 'This is a reminder that your WordPress SALT keys are scheduled to change on %s.', 'add-some-solt' ),
			$change_date
		);
		
		$message .= "\n\n" . __( 'This is an automated change managed by the Add Some Solt plugin.', 'add-some-solt' );
		$message .= "\n\n" . __( 'All users will be logged out when the keys change.', 'add-some-solt' );
		$message .= "\n\n" . admin_url( 'admin.php?page=add-some-solt' );

		wp_mail( $to, $subject, $message );
	}

	private function send_change_notification() {
		$to = $this->database->get_setting( 'notification_email', get_option( 'admin_email' ) );
		$subject = __( 'SALT Keys Have Been Changed', 'add-some-solt' );
		
		$message = __( 'Your WordPress SALT keys have been automatically changed.', 'add-some-solt' );
		$message .= "\n\n" . __( 'All users have been logged out for security.', 'add-some-solt' );
		$message .= "\n\n" . __( 'Change Date: ', 'add-some-solt' ) . current_time( 'mysql' );
		$message .= "\n\n" . admin_url( 'admin.php?page=add-some-solt' );

		wp_mail( $to, $subject, $message );
	}

	public function clear_scheduled_changes() {
		wp_clear_scheduled_hook( 'ass_scheduled_key_change' );
		wp_clear_scheduled_hook( 'ass_send_reminder' );
	}

	public function get_next_scheduled_time() {
		$next_change = wp_next_scheduled( 'ass_scheduled_key_change' );
		
		if ( false === $next_change ) {
			return false;
		}

		return $next_change;
	}

	public function test_schedule() {
		$test_time = current_time( 'timestamp' ) + 60;
		
		wp_schedule_single_event( $test_time, 'ass_scheduled_key_change' );

		return $test_time;
	}
}