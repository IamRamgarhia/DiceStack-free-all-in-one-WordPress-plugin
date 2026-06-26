<?php
/**
 * Login Page Customizer module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Brands the wp-login.php screen with a custom logo and background. Replaces
 * the login-branding part of White Label CMS.
 */
final class Login_Page_Customizer extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'login_page_customizer';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Login page customizer', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add your logo and brand colours to the WordPress login screen.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'admin';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'lock';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium white-label plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 25,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 1,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function settings_schema() {
		return array(
			array(
				'key'     => 'logo',
				'label'   => __( 'Logo URL', 'dicestack' ),
				'type'    => 'url',
				'default' => '',
			),
			array(
				'key'     => 'bg_color',
				'label'   => __( 'Page background colour', 'dicestack' ),
				'type'    => 'color',
				'default' => '#1b2a4a',
			),
			array(
				'key'     => 'button_color',
				'label'   => __( 'Button colour', 'dicestack' ),
				'type'    => 'color',
				'default' => '#0aa2c0',
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'login_enqueue_scripts', array( $this, 'styles' ) );
		add_filter( 'login_headerurl', array( $this, 'logo_url' ) );
		add_filter( 'login_headertext', array( $this, 'logo_text' ) );
	}

	/**
	 * Output inline login styles.
	 *
	 * @return void
	 */
	public function styles() {
		$s      = $this->get_settings();
		$logo   = esc_url( (string) $s['logo'] );
		$bg     = sanitize_hex_color( (string) $s['bg_color'] ) ?: '#1b2a4a';
		$button = sanitize_hex_color( (string) $s['button_color'] ) ?: '#0aa2c0';

		echo '<style>';
		echo 'body.login{background:' . esc_attr( $bg ) . ';}';
		echo '.login label,.login #nav a,.login #backtoblog a{color:rgba(255,255,255,.85)!important;}';
		echo '.wp-core-ui .button-primary{background:' . esc_attr( $button ) . '!important;border-color:' . esc_attr( $button ) . '!important;}';
		if ( $logo ) {
			echo '.login h1 a{background-image:url(' . esc_url( $logo ) . ')!important;background-size:contain!important;width:auto!important;height:72px!important;}';
		}
		echo '</style>';
	}

	/**
	 * Point the login logo link at the site home.
	 *
	 * @return string
	 */
	public function logo_url() {
		return home_url( '/' );
	}

	/**
	 * Logo link title text.
	 *
	 * @return string
	 */
	public function logo_text() {
		return get_bloginfo( 'name' );
	}
}
