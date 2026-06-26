<?php
/**
 * Hide PHP Errors module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Prevents PHP warnings/notices from being printed to visitors on the front end,
 * which can leak server paths. Errors are still logged for admins.
 */
final class Hide_PHP_Errors extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'hide_php_errors';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Hide PHP errors from visitors', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Stop PHP warnings leaking to the front end (they can reveal server paths).', 'dicestack' );
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
		return 'shield';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 8,
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
		// Don't suppress for logged-in admins so they can still debug.
		if ( is_admin() || current_user_can( 'manage_options' ) ) {
			return;
		}
		// phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed, Squiz.PHP.DiscouragedFunctions.Discouraged, WordPress.PHP.NoSilencedErrors.Discouraged -- intentionally hides PHP errors from front-end visitors.
		@ini_set( 'display_errors', '0' );
	}
}
