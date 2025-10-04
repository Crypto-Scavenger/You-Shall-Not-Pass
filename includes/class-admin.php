<?php
/**
 * Admin interface for Add Some Solt
 *
 * @package AddSomeSolt
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASS_Admin {

	private $core;
	private $database;
	private $salt_manager;
	private $scheduler;

	public function __construct( $core, $database, $salt_manager, $scheduler ) {
		$this->core = $core;
		$this->database = $database;
		$this->salt_manager = $salt_manager;
		$this->scheduler = $scheduler;

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add_admin_menu() {
		add_management_page(
			__( 'Add Some Solt', 'add-some-solt' ),
			__( 'SALT Keys', 'add-some-solt' ),
			'manage_options',
			'add-some-solt',
			array( $this, 'render_admin_page' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( 'tools_page_add-some-solt' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'ass-admin-styles',
			ASS_URL . 'assets/admin.css',
			array(),
			ASS_VERSION
		);

		wp_enqueue_script(
			'ass-admin-scripts',
			ASS_URL . 'assets/admin.js',
			array( 'jquery' ),
			ASS_VERSION,
			true
		);
	}

	public function handle_actions() {
		if ( ! isset( $_POST['ass_action'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'add-some-solt' ) );
		}

		if ( ! isset( $_POST['ass_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ass_nonce'] ) ), 'ass_action' ) ) {
			wp_die( esc_html__( 'Security check failed', 'add-some-solt' ) );
		}

		$action = sanitize_text_field( wp_unslash( $_POST['ass_action'] ) );

		switch ( $action ) {
			case 'save_settings':
				$this->save_settings();
				break;

			case 'generate_keys':
				$this->generate_keys();
				break;

			case 'test_schedule':
				$this->test_schedule();
				break;
		}
	}

	private function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'add-some-solt' ) );
		}

		$settings = array(
			'schedule_enabled' => isset( $_POST['schedule_enabled'] ) ? '1' : '0',
			'schedule_frequency' => isset( $_POST['schedule_frequency'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule_frequency'] ) ) : 'quarterly',
			'schedule_day' => isset( $_POST['schedule_day'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule_day'] ) ) : '1',
			'schedule_time' => isset( $_POST['schedule_time'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule_time'] ) ) : '03:00',
			'email_notifications' => isset( $_POST['email_notifications'] ) ? '1' : '0',
			'notification_email' => isset( $_POST['notification_email'] ) ? sanitize_email( wp_unslash( $_POST['notification_email'] ) ) : get_option( 'admin_email' ),
			'reminder_enabled' => isset( $_POST['reminder_enabled'] ) ? '1' : '0',
			'reminder_days' => isset( $_POST['reminder_days'] ) ? absint( $_POST['reminder_days'] ) : '7',
			'cleanup_on_uninstall' => isset( $_POST['cleanup_on_uninstall'] ) ? '1' : '0',
		);

		foreach ( $settings as $key => $value ) {
			$this->database->save_setting( $key, $value );
		}

		if ( '1' === $settings['schedule_enabled'] ) {
			$this->scheduler->schedule_key_change(
				$settings['schedule_frequency'],
				$settings['schedule_day'],
				$settings['schedule_time']
			);
		} else {
			$this->scheduler->clear_scheduled_changes();
		}

		wp_safe_redirect( add_query_arg(
			array(
				'page' => 'add-some-solt',
				'settings-updated' => 'true',
			),
			admin_url( 'tools.php' )
		) );
		exit;
	}

	private function generate_keys() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'add-some-solt' ) );
		}

		$result = $this->core->manual_key_change();

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg(
				array(
					'page' => 'add-some-solt',
					'error' => urlencode( $result->get_error_message() ),
				),
				admin_url( 'tools.php' )
			) );
			exit;
		}

		wp_safe_redirect( add_query_arg(
			array(
				'page' => 'add-some-solt',
				'keys-updated' => 'true',
			),
			admin_url( 'tools.php' )
		) );
		exit;
	}

	private function test_schedule() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'add-some-solt' ) );
		}

		$test_time = $this->scheduler->test_schedule();

		wp_safe_redirect( add_query_arg(
			array(
				'page' => 'add-some-solt',
				'test-scheduled' => 'true',
				'test-time' => $test_time,
			),
			admin_url( 'tools.php' )
		) );
		exit;
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'add-some-solt' ) );
		}

		$settings = $this->database->get_all_settings();
		$current_keys = $this->salt_manager->get_current_keys();
		$writable_check = $this->salt_manager->check_config_writable();
		$next_scheduled = $this->scheduler->get_next_scheduled_time();
		$change_log = $this->database->get_change_log( 20 );

		?>
		<div class="wrap ass-admin-page">
			<h1><?php esc_html_e( 'Add Some Solt - SALT Key Management', 'add-some-solt' ); ?></h1>

			<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully.', 'add-some-solt' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['keys-updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'SALT keys have been updated successfully. All users have been logged out.', 'add-some-solt' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['test-scheduled'] ) && isset( $_GET['test-time'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						$test_time = absint( $_GET['test-time'] );
						echo esc_html( sprintf(
							__( 'Test scheduled for %s (1 minute from now). Check the change log after that time.', 'add-some-solt' ),
							wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $test_time )
						) );
						?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['error'] ) ) : ?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html( urldecode( sanitize_text_field( wp_unslash( $_GET['error'] ) ) ) ); ?></p>
				</div>
			<?php endif; ?>

			<div class="ass-status-box">
				<h2><?php esc_html_e( 'wp-config.php Status', 'add-some-solt' ); ?></h2>
				<?php if ( $writable_check['writable'] ) : ?>
					<p class="ass-status-success">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php echo esc_html( $writable_check['message'] ); ?>
					</p>
					<?php if ( isset( $writable_check['path'] ) ) : ?>
						<p class="description"><?php echo esc_html( sprintf( __( 'Path: %s', 'add-some-solt' ), $writable_check['path'] ) ); ?></p>
					<?php endif; ?>
				<?php else : ?>
					<p class="ass-status-error">
						<span class="dashicons dashicons-warning"></span>
						<?php echo esc_html( $writable_check['message'] ); ?>
					</p>
					<?php if ( isset( $writable_check['path'] ) ) : ?>
						<p class="description"><?php echo esc_html( sprintf( __( 'Path: %s', 'add-some-solt' ), $writable_check['path'] ) ); ?></p>
						<p class="description"><?php esc_html_e( 'You may need to temporarily change file permissions to allow updates.', 'add-some-solt' ); ?></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<h2><?php esc_html_e( 'Current SALT Keys', 'add-some-solt' ); ?></h2>
			<p><?php esc_html_e( 'These are your currently active SALT keys from wp-config.php:', 'add-some-solt' ); ?></p>
			
			<div class="ass-keys-container">
				<button type="button" id="ass-toggle-keys" class="button">
					<?php esc_html_e( 'Show Keys', 'add-some-solt' ); ?>
				</button>
				
				<div id="ass-keys-display" style="display: none; margin-top: 15px;">
					<table class="widefat">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Key Name', 'add-some-solt' ); ?></th>
								<th><?php esc_html_e( 'Value', 'add-some-solt' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $current_keys as $key_name => $key_value ) : ?>
								<tr>
									<td><code><?php echo esc_html( $key_name ); ?></code></td>
									<td><code class="ass-key-value"><?php echo esc_html( $key_value ); ?></code></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

			<h2><?php esc_html_e( 'Generate New Keys', 'add-some-solt' ); ?></h2>
			<p><?php esc_html_e( 'Generate and replace your SALT keys immediately. This will log out all users.', 'add-some-solt' ); ?></p>
			
			<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure? This will log out all users immediately.', 'add-some-solt' ) ); ?>');">
				<?php wp_nonce_field( 'ass_action', 'ass_nonce' ); ?>
				<input type="hidden" name="ass_action" value="generate_keys">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Generate & Replace Keys Now', 'add-some-solt' ); ?>
				</button>
			</form>

			<h2><?php esc_html_e( 'Scheduled Key Changes', 'add-some-solt' ); ?></h2>
			
			<?php if ( $next_scheduled ) : ?>
				<div class="notice notice-info inline">
					<p>
						<strong><?php esc_html_e( 'Next Scheduled Change:', 'add-some-solt' ); ?></strong>
						<?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_scheduled ) ); ?>
					</p>
				</div>
			<?php endif; ?>

			<form method="post" class="ass-settings-form">
				<?php wp_nonce_field( 'ass_action', 'ass_nonce' ); ?>
				<input type="hidden" name="ass_action" value="save_settings">

				<table class="form-table">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Enable Scheduled Changes', 'add-some-solt' ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="schedule_enabled" value="1" <?php checked( $settings['schedule_enabled'] ?? '0', '1' ); ?>>
								<?php esc_html_e( 'Automatically change SALT keys on a schedule', 'add-some-solt' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="schedule_frequency"><?php esc_html_e( 'Frequency', 'add-some-solt' ); ?></label>
						</th>
						<td>
							<select name="schedule_frequency" id="schedule_frequency">
								<option value="daily" <?php selected( $settings['schedule_frequency'] ?? 'quarterly', 'daily' ); ?>><?php esc_html_e( 'Daily', 'add-some-solt' ); ?></option>
								<option value="weekly" <?php selected( $settings['schedule_frequency'] ?? 'quarterly', 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'add-some-solt' ); ?></option>
								<option value="monthly" <?php selected( $settings['schedule_frequency'] ?? 'quarterly', 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'add-some-solt' ); ?></option>
								<option value="quarterly" <?php selected( $settings['schedule_frequency'] ?? 'quarterly', 'quarterly' ); ?>><?php esc_html_e( 'Quarterly', 'add-some-solt' ); ?></option>
								<option value="biannually" <?php selected( $settings['schedule_frequency'] ?? 'quarterly', 'biannually' ); ?>><?php esc_html_e( 'Biannually', 'add-some-solt' ); ?></option>
							</select>
						</td>
					</tr>

					<tr id="schedule_day_row">
						<th scope="row">
							<label for="schedule_day"><?php esc_html_e( 'Day', 'add-some-solt' ); ?></label>
						</th>
						<td>
							<select name="schedule_day" id="schedule_day">
								<?php for ( $i = 1; $i <= 31; $i++ ) : ?>
									<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $settings['schedule_day'] ?? '1', $i ); ?>>
										<?php echo esc_html( $i ); ?>
									</option>
								<?php endfor; ?>
							</select>
							<p class="description" id="day_description"></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="schedule_time"><?php esc_html_e( 'Time', 'add-some-solt' ); ?></label>
						</th>
						<td>
							<input type="time" name="schedule_time" id="schedule_time" value="<?php echo esc_attr( $settings['schedule_time'] ?? '03:00' ); ?>">
							<p class="description"><?php esc_html_e( 'Time in 24-hour format (server timezone)', 'add-some-solt' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Email Notifications', 'add-some-solt' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Enable Notifications', 'add-some-solt' ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="email_notifications" value="1" <?php checked( $settings['email_notifications'] ?? '1', '1' ); ?>>
								<?php esc_html_e( 'Send email notification when keys are changed', 'add-some-solt' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="notification_email"><?php esc_html_e( 'Email Address', 'add-some-solt' ); ?></label>
						</th>
						<td>
							<input type="email" name="notification_email" id="notification_email" value="<?php echo esc_attr( $settings['notification_email'] ?? get_option( 'admin_email' ) ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Email address for notifications', 'add-some-solt' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<?php esc_html_e( 'Enable Reminders', 'add-some-solt' ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="reminder_enabled" value="1" <?php checked( $settings['reminder_enabled'] ?? '0', '1' ); ?>>
								<?php esc_html_e( 'Send reminder email before scheduled changes', 'add-some-solt' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="reminder_days"><?php esc_html_e( 'Reminder Days Before', 'add-some-solt' ); ?></label>
						</th>
						<td>
							<input type="number" name="reminder_days" id="reminder_days" value="<?php echo esc_attr( $settings['reminder_days'] ?? '7' ); ?>" min="1" max="30" class="small-text">
							<p class="description"><?php esc_html_e( 'Number of days before scheduled change to send reminder', 'add-some-solt' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Cleanup', 'add-some-solt' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Cleanup on Uninstall', 'add-some-solt' ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="cleanup_on_uninstall" value="1" <?php checked( $settings['cleanup_on_uninstall'] ?? '1', '1' ); ?>>
								<?php esc_html_e( 'Remove all plugin data when uninstalling', 'add-some-solt' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Save Settings', 'add-some-solt' ); ?>
					</button>
				</p>
			</form>

			<h2><?php esc_html_e( 'Test Scheduled Change', 'add-some-solt' ); ?></h2>
			<p><?php esc_html_e( 'Test the scheduling system by scheduling a key change for 1 minute from now. This WILL change your keys and log out all users.', 'add-some-solt' ); ?></p>
			
			<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'This will actually change your SALT keys in 1 minute. Continue?', 'add-some-solt' ) ); ?>');">
				<?php wp_nonce_field( 'ass_action', 'ass_nonce' ); ?>
				<input type="hidden" name="ass_action" value="test_schedule">
				<button type="submit" class="button">
					<?php esc_html_e( 'Test Schedule (Changes Keys in 1 Minute)', 'add-some-solt' ); ?>
				</button>
			</form>

			<h2><?php esc_html_e( 'Change History', 'add-some-solt' ); ?></h2>
			
			<?php if ( empty( $change_log ) ) : ?>
				<p><?php esc_html_e( 'No changes recorded yet.', 'add-some-solt' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'add-some-solt' ); ?></th>
							<th><?php esc_html_e( 'Type', 'add-some-solt' ); ?></th>
							<th><?php esc_html_e( 'User', 'add-some-solt' ); ?></th>
							<th><?php esc_html_e( 'Notes', 'add-some-solt' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $change_log as $log_entry ) : ?>
							<tr>
								<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log_entry['change_date'] ) ) ); ?></td>
								<td><?php echo esc_html( $log_entry['change_type'] ); ?></td>
								<td>
									<?php
									if ( $log_entry['changed_by'] ) {
										$user = get_userdata( $log_entry['changed_by'] );
										echo esc_html( $user ? $user->display_name : __( 'Unknown', 'add-some-solt' ) );
									} else {
										esc_html_e( 'System', 'add-some-solt' );
									}
									?>
								</td>
								<td><?php echo esc_html( $log_entry['notes'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}