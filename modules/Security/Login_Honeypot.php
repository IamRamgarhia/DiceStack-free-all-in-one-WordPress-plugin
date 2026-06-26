<?php
/**
 * Login Honeypot module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a hidden honeypot field to the login form; bots that fill it are rejected.
 * Invisible to real users.
 */
final class Login_Honeypot extends Abstract_Module {

	/**
	 * Honeypot field name.
	 */
	const FIELD = 'dicestack_lh';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'login_honeypot';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Login honeypot', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Trap bots that auto-submit the login form with a hidden honeypot field.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'security';
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
	public function performance_profile() {
		return array(
			'php_memory_kb' => 12,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 0,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'login_form', array( $this, 'field' ) );
		add_filter( 'authenticate', array( $this, 'check' ), 21 );
	}

	/**
	 * Output the honeypot field.
	 *
	 * @return void
	 */
	public function field() {
		echo '<p style="position:absolute;left:-9999px;" aria-hidden="true"><label>' . esc_html__( 'Leave blank', 'dicestack' ) . '<input type="text" name="' . esc_attr( self::FIELD ) . '" tabindex="-1" autocomplete="off" /></label></p>';
	}

	/**
	 * Reject logins where the honeypot is filled.
	 *
	 * @param mixed $user Auth result.
	 * @return mixed
	 */
	public function check( $user ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- login flow; only checking whether our honeypot field is non-empty.
		if ( isset( $_POST[ self::FIELD ] ) && '' !== trim( (string) wp_unslash( $_POST[ self::FIELD ] ) ) ) {
			return new \WP_Error( 'dicestack_honeypot', __( 'Login blocked.', 'dicestack' ) );
		}
		return $user;
	}
}
