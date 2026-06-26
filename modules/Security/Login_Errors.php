<?php
/**
 * Generic Login Errors module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Replaces WordPress's revealing login error ("unknown username" vs "wrong
 * password") with a single generic message so attackers can't enumerate users.
 */
final class Login_Errors extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'login_errors';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Generic login errors', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Hide whether a username exists by showing one generic login error message.', 'dicestack' );
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
		add_filter( 'login_errors', array( $this, 'generic' ) );
	}

	/**
	 * Return a generic error message.
	 *
	 * @param string $error Original error HTML.
	 * @return string
	 */
	public function generic( $error ) {
		return __( 'Invalid login details. Please try again.', 'dicestack' );
	}
}
