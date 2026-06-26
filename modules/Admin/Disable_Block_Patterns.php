<?php
/**
 * Disable Block Patterns module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the core/remote block patterns and the pattern directory from the
 * editor for a simpler, faster inserter on content-focused sites.
 */
final class Disable_Block_Patterns extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'disable_block_patterns';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Disable block patterns', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Hide core and remote block patterns for a cleaner, faster editor inserter.', 'dicestack' );
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
		return 'layout-grid';
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
		add_filter( 'should_load_remote_block_patterns', '__return_false' );
		remove_theme_support( 'core-block-patterns' );
	}
}
