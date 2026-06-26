<?php
/**
 * Disable jQuery Migrate module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the jquery-migrate script on the front end. Modern themes and plugins
 * rarely need it, and it's a small request saved on every page.
 */
final class Disable_jQuery_Migrate extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'disable_jquery_migrate';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Disable jQuery Migrate', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Stop loading the legacy jQuery Migrate script on the front end.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'performance';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'bolt';
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
		if ( is_admin() ) {
			return;
		}
		add_action( 'wp_default_scripts', array( $this, 'remove_migrate' ) );
	}

	/**
	 * Remove jquery-migrate from jQuery's dependencies.
	 *
	 * @param \WP_Scripts $scripts Scripts registry.
	 * @return void
	 */
	public function remove_migrate( $scripts ) {
		if ( is_admin() ) {
			return;
		}
		$jquery = isset( $scripts->registered['jquery'] ) ? $scripts->registered['jquery'] : null;
		if ( $jquery && ! empty( $jquery->deps ) ) {
			$jquery->deps = array_diff( $jquery->deps, array( 'jquery-migrate' ) );
		}
	}
}
