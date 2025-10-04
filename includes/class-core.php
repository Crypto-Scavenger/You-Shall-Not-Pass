<?php
/**
 * Core functionality for You Shall Not Pass
 *
 * @package YouShallNotPass
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YSNP_Core {
	
	private $database;
	
	public function __construct( $database ) {
		$this->database = $database;
		
		add_action( 'template_redirect', array( $this, 'restrict_content' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'login_redirect', array( $this, 'redirect_after_login' ), 10, 3 );
	}
	
	public function restrict_content() {
		if ( is_user_logged_in() ) {
			return;
		}
		
		$enabled = $this->database->get_setting( 'enabled', '1' );
		if ( '1' !== $enabled ) {
			return;
		}
		
		if ( $GLOBALS['pagenow'] === 'wp-login.php' ) {
			return;
		}
		
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}
		
		nocache_headers();
		
		$this->render_restriction_page();
		exit;
	}
	
	private function render_restriction_page() {
		$settings = $this->database->get_all_settings();
		
		$page_title     = isset( $settings['page_title'] ) ? $settings['page_title'] : 'Access Restricted';
		$page_heading   = isset( $settings['page_heading'] ) ? $settings['page_heading'] : 'You Shall Not Pass';
		$page_message   = isset( $settings['page_message'] ) ? $settings['page_message'] : 'This content is restricted. Please log in to access.';
		$show_logo      = isset( $settings['show_logo'] ) ? $settings['show_logo'] : '1';
		$logo_text      = isset( $settings['logo_text'] ) ? $settings['logo_text'] : 'RESTRICTED AREA';
		
		$username_label = isset( $settings['form_username_label'] ) ? $settings['form_username_label'] : 'Username';
		$password_label = isset( $settings['form_password_label'] ) ? $settings['form_password_label'] : 'Password';
		$button_text    = isset( $settings['form_button_text'] ) ? $settings['form_button_text'] : 'Enter';
		$remember_label = isset( $settings['form_remember_label'] ) ? $settings['form_remember_label'] : 'Remember Me';
		$show_remember  = isset( $settings['show_remember'] ) ? $settings['show_remember'] : '1';
		$show_lost_pwd  = isset( $settings['show_lost_password'] ) ? $settings['show_lost_password'] : '1';
		$lost_pwd_text  = isset( $settings['lost_password_text'] ) ? $settings['lost_password_text'] : 'Lost your password?';
		
		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : home_url();
		
		if ( empty( $redirect_to ) || $redirect_to === 'wp-admin/' || $redirect_to === admin_url() ) {
			$redirect_to = home_url();
		}
		
		$login_url = wp_login_url( $redirect_to );
		
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<meta name="robots" content="noindex, nofollow">
			<title><?php echo esc_html( $page_title ); ?></title>
			<?php wp_head(); ?>
		</head>
		<body class="ysnp-restriction-page">
			<div class="ysnp-container">
				<?php if ( '1' === $show_logo ) : ?>
					<div class="ysnp-logo">
						<i class="fas fa-shield-alt"></i>
						<div class="ysnp-logo-text"><?php echo esc_html( $logo_text ); ?></div>
					</div>
				<?php endif; ?>
				
				<div class="ysnp-content">
					<h1 class="ysnp-heading"><?php echo esc_html( $page_heading ); ?></h1>
					<p class="ysnp-message"><?php echo esc_html( $page_message ); ?></p>
					
					<div class="ysnp-form-wrapper">
						<form name="loginform" id="ysnp-loginform" action="<?php echo esc_url( $login_url ); ?>" method="post">
							<div class="ysnp-form-group">
								<label for="user_login">
									<i class="fas fa-user"></i>
									<?php echo esc_html( $username_label ); ?>
								</label>
								<input type="text" name="log" id="user_login" class="ysnp-input" required autocomplete="username">
							</div>
							
							<div class="ysnp-form-group">
								<label for="user_pass">
									<i class="fas fa-lock"></i>
									<?php echo esc_html( $password_label ); ?>
								</label>
								<input type="password" name="pwd" id="user_pass" class="ysnp-input" required autocomplete="current-password">
							</div>
							
							<?php if ( '1' === $show_remember ) : ?>
								<div class="ysnp-form-group ysnp-remember">
									<label>
										<input name="rememberme" type="checkbox" id="rememberme" value="forever">
										<?php echo esc_html( $remember_label ); ?>
									</label>
								</div>
							<?php endif; ?>
							
							<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
							
							<button type="submit" name="wp-submit" id="wp-submit" class="ysnp-button">
								<?php echo esc_html( $button_text ); ?>
								<i class="fas fa-arrow-right"></i>
							</button>
							
							<?php if ( '1' === $show_lost_pwd ) : ?>
								<div class="ysnp-form-footer">
									<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="ysnp-link">
										<i class="fas fa-key"></i>
										<?php echo esc_html( $lost_pwd_text ); ?>
									</a>
								</div>
							<?php endif; ?>
						</form>
					</div>
				</div>
				
				<div class="ysnp-footer">
					<div class="ysnp-scanner"></div>
				</div>
			</div>
			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
	}
	
	public function enqueue_assets() {
		if ( is_user_logged_in() ) {
			return;
		}
		
		$enabled = $this->database->get_setting( 'enabled', '1' );
		if ( '1' !== $enabled ) {
			return;
		}
		
		wp_enqueue_style(
			'ysnp-public',
			YSNP_URL . 'assets/public.css',
			array(),
			YSNP_VERSION
		);
		
		$settings = $this->database->get_all_settings();
		
		$custom_css = ':root {';
		$custom_css .= '--ysnp-bg-color: ' . esc_attr( isset( $settings['background_color'] ) ? $settings['background_color'] : '#262626' ) . ';';
		$custom_css .= '--ysnp-text-color: ' . esc_attr( isset( $settings['text_color'] ) ? $settings['text_color'] : '#ffffff' ) . ';';
		$custom_css .= '--ysnp-accent-color: ' . esc_attr( isset( $settings['accent_color'] ) ? $settings['accent_color'] : '#d11c1c' ) . ';';
		$custom_css .= '--ysnp-form-bg: ' . esc_attr( isset( $settings['form_bg_color'] ) ? $settings['form_bg_color'] : 'rgba(38, 38, 38, 0.9)' ) . ';';
		$custom_css .= '--ysnp-form-border: ' . esc_attr( isset( $settings['form_border_color'] ) ? $settings['form_border_color'] : '#d11c1c' ) . ';';
		$custom_css .= '--ysnp-input-bg: ' . esc_attr( isset( $settings['input_bg_color'] ) ? $settings['input_bg_color'] : 'rgba(0, 0, 0, 0.5)' ) . ';';
		$custom_css .= '--ysnp-input-text: ' . esc_attr( isset( $settings['input_text_color'] ) ? $settings['input_text_color'] : '#ffffff' ) . ';';
		$custom_css .= '--ysnp-input-border: ' . esc_attr( isset( $settings['input_border_color'] ) ? $settings['input_border_color'] : 'rgba(209, 28, 28, 0.3)' ) . ';';
		$custom_css .= '--ysnp-button-bg: ' . esc_attr( isset( $settings['button_bg_color'] ) ? $settings['button_bg_color'] : '#d11c1c' ) . ';';
		$custom_css .= '--ysnp-button-text: ' . esc_attr( isset( $settings['button_text_color'] ) ? $settings['button_text_color'] : '#ffffff' ) . ';';
		$custom_css .= '--ysnp-button-hover: ' . esc_attr( isset( $settings['button_hover_color'] ) ? $settings['button_hover_color'] : '#ff1c1c' ) . ';';
		$custom_css .= '}';
		
		if ( ! empty( $settings['custom_css'] ) ) {
			$custom_css .= "\n" . $settings['custom_css'];
		}
		
		wp_add_inline_style( 'ysnp-public', $custom_css );
	}
	
	public function redirect_after_login( $redirect_to, $request, $user ) {
		if ( ! is_wp_error( $user ) && isset( $user->ID ) ) {
			if ( ! empty( $request ) ) {
				return $request;
			}
			return home_url();
		}
		return $redirect_to;
	}
}
