<?php
/**
 * Remove Admin Footer Version module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the WordPress version number shown in the bottom-right of wp-admin.
 */
final class Remove_Footer_Version extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'remove_footer_version';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Hide admin version', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Remove the WordPress version number from the admin footer.', 'dicestack' );
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
		add_filter( 'update_footer', '__return_empty_string', 11 );
	}
}
