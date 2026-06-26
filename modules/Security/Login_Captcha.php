<?php
/**
 * Login Math Captcha module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a simple math question to the wp-login form to stop automated login
 * attempts — no external CAPTCHA service. The answer is verified via a salted
 * hash so it works statelessly.
 */
final class Login_Captcha extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'login_captcha';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Login math captcha', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Require a simple sum to log in, blocking automated login bots — no external service.', 'dicestack' );
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
			'php_memory_kb' => 14,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 0,
			'external_http' => 0,
		);
	}

	/**
	 * Hash an answer with WordPress's salt.
	 *
	 * @param int $answer Numeric answer.
	 * @return string
	 */
	private function hash( $answer ) {
		return wp_hash( 'dicestack_login_math_' . $answer );
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'login_form', array( $this, 'field' ) );
		add_filter( 'authenticate', array( $this, 'verify' ), 25 );
	}

	/**
	 * Output the captcha field on the login form.
	 *
	 * @return void
	 */
	public function field() {
		$a   = wp_rand( 1, 9 );
		$b   = wp_rand( 1, 9 );
		echo '<p><label for="dicestack_login_math">' . sprintf( /* translators: 1: first number, 2: second number. */ esc_html__( 'What is %1$d + %2$d?', 'dicestack' ), (int) $a, (int) $b ) . '<br/>';
		echo '<input type="text" name="dicestack_login_math" id="dicestack_login_math" class="input" size="20" autocomplete="off" /></label>';
		echo '<input type="hidden" name="dicestack_login_math_h" value="' . esc_attr( $this->hash( $a + $b ) ) . '" /></p>';
	}

	/**
	 * Verify the captcha answer during authentication.
	 *
	 * @param mixed $user Auth result.
	 * @return mixed
	 */
	public function verify( $user ) {
		// Don't override an error from an earlier filter (e.g. brute-force lockout).
		if ( is_wp_error( $user ) ) {
			return $user;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- core login flow; reading our captcha fields only.
		// Fail open when our field wasn't rendered (custom login form, caching),
		// so legitimate logins are never blocked by a missing captcha.
		if ( empty( $_POST['log'] ) || empty( $_POST['dicestack_login_math_h'] ) ) {
			return $user;
		}
		$answer = isset( $_POST['dicestack_login_math'] ) ? absint( wp_unslash( $_POST['dicestack_login_math'] ) ) : -1;
		$hash   = sanitize_text_field( wp_unslash( $_POST['dicestack_login_math_h'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! hash_equals( $hash, $this->hash( $answer ) ) ) {
			return new \WP_Error( 'dicestack_login_captcha', __( '<strong>Error:</strong> Incorrect answer to the math question.', 'dicestack' ) );
		}
		return $user;
	}
}
