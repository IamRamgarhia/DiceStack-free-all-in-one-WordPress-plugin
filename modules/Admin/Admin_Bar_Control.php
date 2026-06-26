<?php
/**
 * Admin Bar Control module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Hides the front-end admin toolbar for selected roles to give clients a
 * cleaner front-end experience.
 */
final class Admin_Bar_Control extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'admin_bar_control';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Front-end toolbar control', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Hide the front-end admin toolbar for everyone except administrators.', 'dicestack' );
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
				'key'     => 'who',
				'label'   => __( 'Hide the toolbar for', 'dicestack' ),
				'type'    => 'select',
				'default' => 'non_admins',
				'options' => array(
					'non_admins' => __( 'Everyone except admins', 'dicestack' ),
					'everyone'   => __( 'Everyone', 'dicestack' ),
				),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'show_admin_bar', array( $this, 'decide' ) );
	}

	/**
	 * Decide whether to show the toolbar.
	 *
	 * @param bool $show Current state.
	 * @return bool
	 */
	public function decide( $show ) {
		$who = $this->get_setting( 'who', 'non_admins' );
		if ( 'everyone' === $who ) {
			return false;
		}
		// non_admins: hide unless the user can manage options.
		return current_user_can( 'manage_options' ) ? $show : false;
	}
}
