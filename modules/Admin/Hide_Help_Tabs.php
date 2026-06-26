<?php
/**
 * Hide Help Tabs module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Hides the contextual "Help" tabs in wp-admin for a cleaner, less confusing
 * interface on client sites.
 */
final class Hide_Help_Tabs extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'hide_help_tabs';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Hide help tabs', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Remove the contextual Help tabs from admin screens for a cleaner interface.', 'dicestack' );
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
		add_action( 'admin_head', array( $this, 'remove_tabs' ) );
	}

	/**
	 * Strip all help tabs on the current screen.
	 *
	 * @return void
	 */
	public function remove_tabs() {
		$screen = get_current_screen();
		if ( $screen ) {
			$screen->remove_help_tabs();
		}
	}
}
