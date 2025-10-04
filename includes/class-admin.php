<?php
/**
 * Admin interface for You Shall Not Pass
 *
 * @package YouShallNotPass
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YSNP_Admin {
	
	private $database;
	
	public function __construct( $database ) {
		$this->database = $database;
		
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}
	
	public function add_admin_menu() {
		add_users_page(
			__( 'You Shall Not Pass Settings', 'you-shall-not-pass' ),
			__( 'Content Restriction', 'you-shall-not-pass' ),
			'manage_options',
			'you-shall-not-pass',
			array( $this, 'render_settings_page' )
		);
	}
	
	public function enqueue_admin_assets( $hook ) {
		if ( 'users_page_you-shall-not-pass' !== $hook ) {
			return;
		}
		
		wp_enqueue_style( 'wp-color-picker' );
		
		wp_enqueue_style(
			'ysnp-admin',
			YSNP_URL . 'assets/admin.css',
			array(),
			YSNP_VERSION
		);
		
		wp_enqueue_script(
			'ysnp-admin',
			YSNP_URL . 'assets/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			YSNP_VERSION,
			true
		);
	}
	
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'you-shall-not-pass' ) );
		}
		
		$settings = $this->database->get_all_settings();
		
		?>
		<div class="wrap ysnp-admin">
			<h1><?php esc_html_e( 'You Shall Not Pass - Content Restriction Settings', 'you-shall-not-pass' ); ?></h1>
			
			<?php if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully.', 'you-shall-not-pass' ); ?></p>
				</div>
			<?php endif; ?>
			
			<div class="ysnp-tabs-wrapper">
				<h2 class="nav-tab-wrapper">
					<a href="#general" class="nav-tab nav-tab-active" data-tab="general">
						<?php esc_html_e( 'General', 'you-shall-not-pass' ); ?>
					</a>
					<a href="#content" class="nav-tab" data-tab="content">
						<?php esc_html_e( 'Page Content', 'you-shall-not-pass' ); ?>
					</a>
					<a href="#form" class="nav-tab" data-tab="form">
						<?php esc_html_e( 'Login Form', 'you-shall-not-pass' ); ?>
					</a>
					<a href="#design" class="nav-tab" data-tab="design">
						<?php esc_html_e( 'Design', 'you-shall-not-pass' ); ?>
					</a>
				</h2>
				
				<form method="post" action="">
					<?php wp_nonce_field( 'ysnp_save_settings', 'ysnp_nonce' ); ?>
					
					<div id="general" class="ysnp-tab-content ysnp-tab-active">
						<h2><?php esc_html_e( 'General Settings', 'you-shall-not-pass' ); ?></h2>
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="enabled"><?php esc_html_e( 'Enable Content Restriction', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<label>
											<input 
												type="checkbox" 
												id="enabled" 
												name="enabled"
												value="1"
												<?php checked( '1', isset( $settings['enabled'] ) ? $settings['enabled'] : '1' ); ?>
											/>
											<?php esc_html_e( 'Restrict content to logged-in users only', 'you-shall-not-pass' ); ?>
										</label>
										<p class="description">
											<?php esc_html_e( 'When enabled, non-logged-in users will only see the login form page.', 'you-shall-not-pass' ); ?>
										</p>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="cleanup_on_uninstall"><?php esc_html_e( 'Cleanup on Uninstall', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<label>
											<input 
												type="checkbox" 
												id="cleanup_on_uninstall" 
												name="cleanup_on_uninstall"
												value="1"
												<?php checked( '1', isset( $settings['cleanup_on_uninstall'] ) ? $settings['cleanup_on_uninstall'] : '0' ); ?>
											/>
											<?php esc_html_e( 'Remove all plugin data when uninstalling', 'you-shall-not-pass' ); ?>
										</label>
										<p class="description">
											<?php esc_html_e( 'If enabled, all settings will be permanently deleted when you uninstall the plugin.', 'you-shall-not-pass' ); ?>
										</p>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					
					<div id="content" class="ysnp-tab-content">
						<h2><?php esc_html_e( 'Page Content Settings', 'you-shall-not-pass' ); ?></h2>
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="page_title"><?php esc_html_e( 'Page Title', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="page_title" 
											name="page_title"
											value="<?php echo esc_attr( isset( $settings['page_title'] ) ? $settings['page_title'] : 'Access Restricted' ); ?>"
											class="regular-text"
										/>
										<p class="description">
											<?php esc_html_e( 'Browser tab title (SEO)', 'you-shall-not-pass' ); ?>
										</p>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="show_logo"><?php esc_html_e( 'Show Logo Section', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<label>
											<input 
												type="checkbox" 
												id="show_logo" 
												name="show_logo"
												value="1"
												<?php checked( '1', isset( $settings['show_logo'] ) ? $settings['show_logo'] : '1' ); ?>
											/>
											<?php esc_html_e( 'Display logo section at the top', 'you-shall-not-pass' ); ?>
										</label>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="logo_text"><?php esc_html_e( 'Logo Text', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="logo_text" 
											name="logo_text"
											value="<?php echo esc_attr( isset( $settings['logo_text'] ) ? $settings['logo_text'] : 'RESTRICTED AREA' ); ?>"
											class="regular-text"
										/>
										<p class="description">
											<?php esc_html_e( 'Text displayed in the logo section', 'you-shall-not-pass' ); ?>
										</p>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="page_heading"><?php esc_html_e( 'Main Heading', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="page_heading" 
											name="page_heading"
											value="<?php echo esc_attr( isset( $settings['page_heading'] ) ? $settings['page_heading'] : 'You Shall Not Pass' ); ?>"
											class="regular-text"
										/>
										<p class="description">
											<?php esc_html_e( 'Large heading displayed on the restriction page', 'you-shall-not-pass' ); ?>
										</p>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="page_message"><?php esc_html_e( 'Page Message', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<textarea 
											id="page_message" 
											name="page_message"
											rows="3"
											class="large-text"
										><?php echo esc_textarea( isset( $settings['page_message'] ) ? $settings['page_message'] : 'This content is restricted. Please log in to access.' ); ?></textarea>
										<p class="description">
											<?php esc_html_e( 'Message displayed below the heading', 'you-shall-not-pass' ); ?>
										</p>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					
					<div id="form" class="ysnp-tab-content">
						<h2><?php esc_html_e( 'Login Form Settings', 'you-shall-not-pass' ); ?></h2>
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="form_username_label"><?php esc_html_e( 'Username Label', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="form_username_label" 
											name="form_username_label"
											value="<?php echo esc_attr( isset( $settings['form_username_label'] ) ? $settings['form_username_label'] : 'Username' ); ?>"
											class="regular-text"
										/>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="form_password_label"><?php esc_html_e( 'Password Label', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="form_password_label" 
											name="form_password_label"
											value="<?php echo esc_attr( isset( $settings['form_password_label'] ) ? $settings['form_password_label'] : 'Password' ); ?>"
											class="regular-text"
										/>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="form_button_text"><?php esc_html_e( 'Submit Button Text', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="form_button_text" 
											name="form_button_text"
											value="<?php echo esc_attr( isset( $settings['form_button_text'] ) ? $settings['form_button_text'] : 'Enter' ); ?>"
											class="regular-text"
										/>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="show_remember"><?php esc_html_e( 'Show "Remember Me"', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<label>
											<input 
												type="checkbox" 
												id="show_remember" 
												name="show_remember"
												value="1"
												<?php checked( '1', isset( $settings['show_remember'] ) ? $settings['show_remember'] : '1' ); ?>
											/>
											<?php esc_html_e( 'Display "Remember Me" checkbox', 'you-shall-not-pass' ); ?>
										</label>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="form_remember_label"><?php esc_html_e( 'Remember Me Label', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="form_remember_label" 
											name="form_remember_label"
											value="<?php echo esc_attr( isset( $settings['form_remember_label'] ) ? $settings['form_remember_label'] : 'Remember Me' ); ?>"
											class="regular-text"
										/>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="show_lost_password"><?php esc_html_e( 'Show "Lost Password"', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<label>
											<input 
												type="checkbox" 
												id="show_lost_password" 
												name="show_lost_password"
												value="1"
												<?php checked( '1', isset( $settings['show_lost_password'] ) ? $settings['show_lost_password'] : '1' ); ?>
											/>
											<?php esc_html_e( 'Display "Lost your password?" link', 'you-shall-not-pass' ); ?>
										</label>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="lost_password_text"><?php esc_html_e( 'Lost Password Link Text', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="lost_password_text" 
											name="lost_password_text"
											value="<?php echo esc_attr( isset( $settings['lost_password_text'] ) ? $settings['lost_password_text'] : 'Lost your password?' ); ?>"
											class="regular-text"
										/>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					
					<div id="design" class="ysnp-tab-content">
						<h2><?php esc_html_e( 'Design & Colors', 'you-shall-not-pass' ); ?></h2>
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="background_color"><?php esc_html_e( 'Background Color', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="background_color" 
											name="background_color"
											value="<?php echo esc_attr( isset( $settings['background_color'] ) ? $settings['background_color'] : '#262626' ); ?>"
											class="ysnp-color-picker"
										/>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="text_color"><?php esc_html_e( 'Text Color', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="text_color" 
											name="text_color"
											value="<?php echo esc_attr( isset( $settings['text_color'] ) ? $settings['text_color'] : '#ffffff' ); ?>"
											class="ysnp-color-picker"
										/>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="accent_color"><?php esc_html_e( 'Accent Color', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="accent_color" 
											name="accent_color"
											value="<?php echo esc_attr( isset( $settings['accent_color'] ) ? $settings['accent_color'] : '#d11c1c' ); ?>"
											class="ysnp-color-picker"
										/>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="form_bg_color"><?php esc_html_e( 'Form Background Color', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="form_bg_color" 
											name="form_bg_color"
											value="<?php echo esc_attr( isset( $settings['form_bg_color'] ) ? $settings['form_bg_color'] : 'rgba(38, 38, 38, 0.9)' ); ?>"
											class="regular-text"
										/>
										<p class="description">
											<?php esc_html_e( 'Supports rgba for transparency', 'you-shall-not-pass' ); ?>
										</p>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="form_border_color"><?php esc_html_e( 'Form Border Color', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="form_border_color" 
											name="form_border_color"
											value="<?php echo esc_attr( isset( $settings['form_border_color'] ) ? $settings['form_border_color'] : '#d11c1c' ); ?>"
											class="ysnp-color-picker"
										/>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="input_bg_color"><?php esc_html_e( 'Input Background Color', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="input_bg_color" 
											name="input_bg_color"
											value="<?php echo esc_attr( isset( $settings['input_bg_color'] ) ? $settings['input_bg_color'] : 'rgba(0, 0, 0, 0.5)' ); ?>"
											class="regular-text"
										/>
										<p class="description">
											<?php esc_html_e( 'Supports rgba for transparency', 'you-shall-not-pass' ); ?>
										</p>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="input_text_color"><?php esc_html_e( 'Input Text Color', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="input_text_color" 
											name="input_text_color"
											value="<?php echo esc_attr( isset( $settings['input_text_color'] ) ? $settings['input_text_color'] : '#ffffff' ); ?>"
											class="ysnp-color-picker"
										/>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="input_border_color"><?php esc_html_e( 'Input Border Color', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="input_border_color" 
											name="input_border_color"
											value="<?php echo esc_attr( isset( $settings['input_border_color'] ) ? $settings['input_border_color'] : 'rgba(209, 28, 28, 0.3)' ); ?>"
											class="regular-text"
										/>
										<p class="description">
											<?php esc_html_e( 'Supports rgba for transparency', 'you-shall-not-pass' ); ?>
										</p>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="button_bg_color"><?php esc_html_e( 'Button Background Color', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="button_bg_color" 
											name="button_bg_color"
											value="<?php echo esc_attr( isset( $settings['button_bg_color'] ) ? $settings['button_bg_color'] : '#d11c1c' ); ?>"
											class="ysnp-color-picker"
										/>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="button_text_color"><?php esc_html_e( 'Button Text Color', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="button_text_color" 
											name="button_text_color"
											value="<?php echo esc_attr( isset( $settings['button_text_color'] ) ? $settings['button_text_color'] : '#ffffff' ); ?>"
											class="ysnp-color-picker"
										/>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="button_hover_color"><?php esc_html_e( 'Button Hover Color', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<input 
											type="text" 
											id="button_hover_color" 
											name="button_hover_color"
											value="<?php echo esc_attr( isset( $settings['button_hover_color'] ) ? $settings['button_hover_color'] : '#ff1c1c' ); ?>"
											class="ysnp-color-picker"
										/>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="custom_css"><?php esc_html_e( 'Custom CSS', 'you-shall-not-pass' ); ?></label>
									</th>
									<td>
										<textarea 
											id="custom_css" 
											name="custom_css"
											rows="10"
											class="large-text code"
										><?php echo esc_textarea( isset( $settings['custom_css'] ) ? $settings['custom_css'] : '' ); ?></textarea>
										<p class="description">
											<?php esc_html_e( 'Add custom CSS for advanced styling', 'you-shall-not-pass' ); ?>
										</p>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					
					<?php submit_button(); ?>
				</form>
			</div>
		</div>
		<?php
	}
	
	public function save_settings() {
		if ( ! isset( $_POST['ysnp_nonce'] ) ) {
			return;
		}
		
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ysnp_nonce'] ) ), 'ysnp_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed', 'you-shall-not-pass' ) );
		}
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'you-shall-not-pass' ) );
		}
		
		$settings = array(
			'enabled'                => isset( $_POST['enabled'] ) ? '1' : '0',
			'page_title'             => isset( $_POST['page_title'] ) ? sanitize_text_field( wp_unslash( $_POST['page_title'] ) ) : 'Access Restricted',
			'page_heading'           => isset( $_POST['page_heading'] ) ? sanitize_text_field( wp_unslash( $_POST['page_heading'] ) ) : 'You Shall Not Pass',
			'page_message'           => isset( $_POST['page_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['page_message'] ) ) : '',
			'show_logo'              => isset( $_POST['show_logo'] ) ? '1' : '0',
			'logo_text'              => isset( $_POST['logo_text'] ) ? sanitize_text_field( wp_unslash( $_POST['logo_text'] ) ) : 'RESTRICTED AREA',
			'form_username_label'    => isset( $_POST['form_username_label'] ) ? sanitize_text_field( wp_unslash( $_POST['form_username_label'] ) ) : 'Username',
			'form_password_label'    => isset( $_POST['form_password_label'] ) ? sanitize_text_field( wp_unslash( $_POST['form_password_label'] ) ) : 'Password',
			'form_button_text'       => isset( $_POST['form_button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['form_button_text'] ) ) : 'Enter',
			'form_remember_label'    => isset( $_POST['form_remember_label'] ) ? sanitize_text_field( wp_unslash( $_POST['form_remember_label'] ) ) : 'Remember Me',
			'show_remember'          => isset( $_POST['show_remember'] ) ? '1' : '0',
			'show_lost_password'     => isset( $_POST['show_lost_password'] ) ? '1' : '0',
			'lost_password_text'     => isset( $_POST['lost_password_text'] ) ? sanitize_text_field( wp_unslash( $_POST['lost_password_text'] ) ) : 'Lost your password?',
			'background_color'       => isset( $_POST['background_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['background_color'] ) ) : '#262626',
			'text_color'             => isset( $_POST['text_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['text_color'] ) ) : '#ffffff',
			'accent_color'           => isset( $_POST['accent_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['accent_color'] ) ) : '#d11c1c',
			'form_bg_color'          => isset( $_POST['form_bg_color'] ) ? sanitize_text_field( wp_unslash( $_POST['form_bg_color'] ) ) : 'rgba(38, 38, 38, 0.9)',
			'form_border_color'      => isset( $_POST['form_border_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['form_border_color'] ) ) : '#d11c1c',
			'input_bg_color'         => isset( $_POST['input_bg_color'] ) ? sanitize_text_field( wp_unslash( $_POST['input_bg_color'] ) ) : 'rgba(0, 0, 0, 0.5)',
			'input_text_color'       => isset( $_POST['input_text_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['input_text_color'] ) ) : '#ffffff',
			'input_border_color'     => isset( $_POST['input_border_color'] ) ? sanitize_text_field( wp_unslash( $_POST['input_border_color'] ) ) : 'rgba(209, 28, 28, 0.3)',
			'button_bg_color'        => isset( $_POST['button_bg_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['button_bg_color'] ) ) : '#d11c1c',
			'button_text_color'      => isset( $_POST['button_text_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['button_text_color'] ) ) : '#ffffff',
			'button_hover_color'     => isset( $_POST['button_hover_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['button_hover_color'] ) ) : '#ff1c1c',
			'custom_css'             => isset( $_POST['custom_css'] ) ? wp_strip_all_tags( wp_unslash( $_POST['custom_css'] ) ) : '',
			'cleanup_on_uninstall'   => isset( $_POST['cleanup_on_uninstall'] ) ? '1' : '0',
		);
		
		foreach ( $settings as $key => $value ) {
			$this->database->save_setting( $key, $value );
		}
		
		wp_safe_redirect( add_query_arg(
			array(
				'page'             => 'you-shall-not-pass',
				'settings-updated' => 'true',
			),
			admin_url( 'users.php' )
		) );
		exit;
	}
}
