<?php
/**
 * Environment Badge module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Shows a coloured environment label in the admin bar (e.g. STAGING, DEV) so
 * you never confuse a test site with production.
 */
final class Environment_Badge extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'environment_badge';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Environment badge', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Display a coloured STAGING/DEV label in the admin bar to avoid mix-ups.', 'dicestack' );
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
		return 'server';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 14,
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
				'key'     => 'label',
				'label'   => __( 'Badge label', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'STAGING', 'dicestack' ),
			),
			array(
				'key'     => 'color',
				'label'   => __( 'Badge colour', 'dicestack' ),
				'type'    => 'color',
				'default' => '#d4537e',
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'admin_bar_menu', array( $this, 'add_badge' ), 100 );
	}

	/**
	 * Add the badge node to the admin bar.
	 *
	 * @param \WP_Admin_Bar $bar Admin bar.
	 * @return void
	 */
	public function add_badge( $bar ) {
		$label = (string) $this->get_setting( 'label', 'STAGING' );
		if ( '' === trim( $label ) ) {
			return;
		}
		$color = sanitize_hex_color( (string) $this->get_setting( 'color', '#d4537e' ) ) ?: '#d4537e';
		$bar->add_node(
			array(
				'id'    => 'dicestack-env',
				'title' => '<span style="background:' . esc_attr( $color ) . ';color:#fff;padding:0 10px;border-radius:3px;font-weight:600;">' . esc_html( $label ) . '</span>',
				'meta'  => array( 'title' => esc_attr__( 'Current environment', 'dicestack' ) ),
			)
		);
	}
}
