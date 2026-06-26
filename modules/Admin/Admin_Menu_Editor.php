<?php
/**
 * Admin Menu Editor module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Hides chosen admin sidebar menu items — ideal for giving a client or extra
 * admin a clean, simplified dashboard. The settings page lists every current
 * top-level menu slug so they're easy to pick.
 */
final class Admin_Menu_Editor extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'admin_menu_editor';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Admin menu editor', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Hide sidebar menu items to give clients a clean, simplified dashboard.', 'dicestack' );
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
	public function replaces() {
		return 'premium menu editors';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 20,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 1,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function settings_schema() {
		return array(
			array(
				'key'     => 'hide',
				'label'   => __( 'Menu slugs to hide', 'dicestack' ),
				'type'    => 'textarea',
				'default' => '',
				'help'    => __( 'One slug per line, e.g. edit-comments.php or tools.php. See "Admin menu" page under DiceStack for the full list.', 'dicestack' ),
			),
			array(
				'key'     => 'apply_admins',
				'label'   => __( 'Also hide for administrators', 'dicestack' ),
				'type'    => 'toggle',
				'default' => false,
				'help'    => __( 'Off = hidden only for non-administrators (recommended).', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'hide_items' ), 9999 );
		add_action( 'admin_menu', array( $this, 'add_reference_page' ) );
	}

	/**
	 * Remove the configured menu items.
	 *
	 * @return void
	 */
	public function hide_items() {
		if ( current_user_can( 'manage_options' ) && empty( $this->get_setting( 'apply_admins', false ) ) ) {
			return;
		}
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $this->get_setting( 'hide', '' ) ) as $slug ) {
			$slug = trim( $slug );
			if ( '' !== $slug && 'dicestack' !== $slug ) {
				remove_menu_page( $slug );
			}
		}
	}

	/**
	 * Register a reference page listing current menu slugs.
	 *
	 * @return void
	 */
	public function add_reference_page() {
		add_submenu_page(
			'dicestack',
			__( 'Admin menu', 'dicestack' ),
			__( 'Admin menu', 'dicestack' ),
			'manage_options',
			'dicestack-admin-menu',
			array( $this, 'render_reference' )
		);
	}

	/**
	 * List the available top-level menu slugs.
	 *
	 * @return void
	 */
	public function render_reference() {
		global $menu;
		echo '<div class="wrap"><h1>' . esc_html__( 'Admin menu editor', 'dicestack' ) . '</h1>';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.
		if ( isset( $_GET['settings-saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'dicestack' ) . '</p></div>';
		}
		echo '<h2>' . esc_html__( 'Settings', 'dicestack' ) . '</h2>';
		echo \DiceStack\Admin\Settings_Renderer::page_form( $this ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped internally.
		echo '<h2>' . esc_html__( 'Menu slugs reference', 'dicestack' ) . '</h2>';
		echo '<p>' . esc_html__( 'Copy a slug below into the Hide menu items field above.', 'dicestack' ) . '</p>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Menu', 'dicestack' ) . '</th><th>' . esc_html__( 'Slug', 'dicestack' ) . '</th></tr></thead><tbody>';
		foreach ( (array) $menu as $item ) {
			if ( empty( $item[0] ) ) {
				continue;
			}
			$label = wp_strip_all_tags( $item[0] );
			$slug  = isset( $item[2] ) ? $item[2] : '';
			echo '<tr><td>' . esc_html( $label ) . '</td><td><code>' . esc_html( $slug ) . '</code></td></tr>';
		}
		echo '</tbody></table></div>';
	}
}
