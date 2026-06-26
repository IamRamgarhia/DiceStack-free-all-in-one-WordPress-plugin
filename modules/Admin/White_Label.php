<?php
/**
 * White Label module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Renames the DiceStack admin menu so agencies can present the toolkit under their
 * own brand to clients.
 */
final class White_Label extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'white_label';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'White label', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Rename the DiceStack menu to your own brand for client sites.', 'dicestack' );
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
		return 'tool';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium white-label plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 12,
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
				'key'     => 'menu_label',
				'label'   => __( 'Menu label', 'dicestack' ),
				'type'    => 'text',
				'default' => 'DiceStack',
				'help'    => __( 'What the toolkit is called in the admin sidebar.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'rename' ), 9999 );
	}

	/**
	 * Rename the DiceStack top-level menu.
	 *
	 * @return void
	 */
	public function rename() {
		global $menu;
		$label = trim( (string) $this->get_setting( 'menu_label', 'DiceStack' ) );
		if ( '' === $label || 'DiceStack' === $label || ! is_array( $menu ) ) {
			return;
		}
		foreach ( $menu as $i => $item ) {
			if ( isset( $item[2] ) && 'dicestack' === $item[2] ) {
				$menu[ $i ][0] = esc_html( $label );
				$menu[ $i ][3] = esc_attr( $label );
				break;
			}
		}
	}
}
