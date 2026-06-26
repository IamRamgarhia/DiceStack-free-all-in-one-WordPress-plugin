<?php
/**
 * Disable Application Passwords module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Disables WordPress Application Passwords for sites that don't use external API
 * clients, removing a credential type that can be abused if leaked.
 */
final class Disable_App_Passwords extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'disable_app_passwords';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Disable application passwords', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Turn off WordPress application passwords if you don\'t use external API clients.', 'dicestack' );
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
			'php_memory_kb' => 10,
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
		add_filter( 'wp_is_application_passwords_available', '__return_false' );
	}
}
