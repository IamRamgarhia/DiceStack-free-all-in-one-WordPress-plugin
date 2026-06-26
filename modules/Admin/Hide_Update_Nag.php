<?php
/**
 * Hide Update Nag module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Hides the WordPress core update nag from users who can't update anyway,
 * keeping client dashboards clean.
 */
final class Hide_Update_Nag extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'hide_update_nag';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Hide update nag', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Hide the core update notice from users who cannot run updates.', 'dicestack' );
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
		add_action( 'admin_init', array( $this, 'maybe_hide' ) );
	}

	/**
	 * Remove the nag for users without update capability.
	 *
	 * @return void
	 */
	public function maybe_hide() {
		if ( ! current_user_can( 'update_core' ) ) {
			remove_action( 'admin_notices', 'update_nag', 3 );
			remove_action( 'network_admin_notices', 'update_nag', 3 );
		}
	}
}
