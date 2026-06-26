<?php
/**
 * Disable Trackbacks module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Turns off trackbacks and pingbacks on new content — they're almost entirely
 * spam these days.
 */
final class Disable_Trackbacks extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'disable_trackbacks';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Disable trackbacks', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Switch off trackbacks and pingbacks, which are almost always spam.', 'dicestack' );
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
		return 'message-off';
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
		add_filter( 'pings_open', '__return_false', 20 );
		// Remove the trackback rewrite endpoint.
		add_filter( 'rewrite_rules_array', array( $this, 'remove_tb_rules' ) );
	}

	/**
	 * Strip trackback rewrite rules.
	 *
	 * @param array $rules Rewrite rules.
	 * @return array
	 */
	public function remove_tb_rules( $rules ) {
		foreach ( $rules as $rule => $target ) {
			if ( false !== strpos( $rule, 'trackback' ) ) {
				unset( $rules[ $rule ] );
			}
		}
		return $rules;
	}
}
