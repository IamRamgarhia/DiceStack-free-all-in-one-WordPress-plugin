<?php
/**
 * Admin Custom CSS module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Lets administrators add custom CSS to the wp-admin interface — handy for
 * tweaking client dashboards without a child plugin.
 */
final class Admin_Custom_CSS extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'admin_custom_css';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Admin custom CSS', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add your own CSS to the admin dashboard for layout and branding tweaks.', 'dicestack' );
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
		return 'code';
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
				'key'     => 'css',
				'label'   => __( 'Admin CSS', 'dicestack' ),
				'type'    => 'textarea',
				'default' => '',
				'help'    => __( 'Applied across wp-admin. Only administrators can edit this.', 'dicestack' ),
			),
		);
	}

	/**
	 * Only allow admins to save raw CSS.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function save_settings( array $input ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->get_settings();
		}
		$clean = array( 'css' => isset( $input['css'] ) ? wp_strip_all_tags( (string) $input['css'] ) : '' );
		update_option( $this->settings_option_key(), $clean );
		return $clean;
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'admin_head', array( $this, 'output' ) );
	}

	/**
	 * Print the admin CSS.
	 *
	 * @return void
	 */
	public function output() {
		$css = (string) $this->get_setting( 'css', '' );
		if ( '' !== trim( $css ) ) {
			echo '<style id="dicestack-admin-css">' . wp_strip_all_tags( $css ) . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS, tags stripped.
		}
	}
}
