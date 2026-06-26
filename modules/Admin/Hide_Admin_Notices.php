<?php
/**
 * Hide Admin Notices module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Suppresses the flood of plugin/theme admin notices for a clean dashboard.
 * Keeps DiceStack's own page and the Plugins screen unaffected so you don't miss
 * anything critical there.
 */
final class Hide_Admin_Notices extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'hide_admin_notices';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Hide admin notices', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Silence plugin notification noise for a clean dashboard (Plugins screen kept intact).', 'dicestack' );
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
	public function settings_schema() {
		return array(
			array(
				'key'     => 'non_admins_only',
				'label'   => __( 'Only hide for non-administrators', 'dicestack' ),
				'type'    => 'toggle',
				'default' => false,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'in_admin_header', array( $this, 'maybe_clear' ), 1000 );
	}

	/**
	 * Remove queued admin notices, except on safe screens.
	 *
	 * @return void
	 */
	public function maybe_clear() {
		if ( ! empty( $this->get_setting( 'non_admins_only', false ) ) && current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		// Keep notices on the Plugins screen and DiceStack's own page.
		if ( $screen && ( 'plugins' === $screen->id || false !== strpos( (string) $screen->id, 'dicestack' ) ) ) {
			return;
		}

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}
}
