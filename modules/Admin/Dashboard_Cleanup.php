<?php
/**
 * Dashboard Cleanup module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Removes clutter from the wp-admin dashboard (WordPress news, default widgets)
 * and optionally the admin-bar WordPress logo. Replaces Adminimize basics.
 */
final class Dashboard_Cleanup extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'dashboard_cleanup';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Dashboard cleanup', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Hide default dashboard widgets and the WordPress logo for a cleaner admin.', 'dicestack' );
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
			'php_memory_kb' => 18,
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
				'key'     => 'remove_widgets',
				'label'   => __( 'Remove default dashboard widgets', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'remove_wp_logo',
				'label'   => __( 'Remove the WordPress logo from the admin bar', 'dicestack' ),
				'type'    => 'toggle',
				'default' => false,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		if ( ! empty( $this->get_setting( 'remove_widgets', true ) ) ) {
			add_action( 'wp_dashboard_setup', array( $this, 'remove_widgets' ) );
		}
		if ( ! empty( $this->get_setting( 'remove_wp_logo', false ) ) ) {
			add_action( 'admin_bar_menu', array( $this, 'remove_wp_logo' ), 999 );
		}
	}

	/**
	 * Remove the noisy default dashboard widgets.
	 *
	 * @return void
	 */
	public function remove_widgets() {
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );       // WordPress events & news.
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );   // Quick draft.
		remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );    // Activity.
		remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );   // At a glance.
	}

	/**
	 * Remove the WP logo node from the admin bar.
	 *
	 * @param \WP_Admin_Bar $bar Admin bar instance.
	 * @return void
	 */
	public function remove_wp_logo( $bar ) {
		$bar->remove_node( 'wp-logo' );
	}
}
