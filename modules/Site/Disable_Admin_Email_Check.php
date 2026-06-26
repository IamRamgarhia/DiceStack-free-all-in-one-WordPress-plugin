<?php
/**
 * Disable Admin Email Verification module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Site;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Disables the periodic "Is this admin email correct?" interstitial WordPress
 * shows on login, which interrupts the workflow on managed sites.
 */
final class Disable_Admin_Email_Check extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'disable_admin_email_check';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Skip admin email verification', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Stop the periodic "confirm your admin email" screen from interrupting logins.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'site';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'mail';
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
		add_filter( 'admin_email_check_interval', '__return_zero' );
	}
}
