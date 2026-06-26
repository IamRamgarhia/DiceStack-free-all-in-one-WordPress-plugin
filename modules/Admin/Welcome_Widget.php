<?php
/**
 * Dashboard Welcome Widget module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a configurable welcome widget to the dashboard — handy for agencies to
 * leave support details for clients. Defaults to a Dice Codes credit.
 */
final class Welcome_Widget extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'welcome_widget';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Dashboard welcome widget', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Show a custom welcome / support note at the top of the dashboard.', 'dicestack' );
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
				'key'     => 'title',
				'label'   => __( 'Widget title', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'Welcome', 'dicestack' ),
			),
			array(
				'key'     => 'body',
				'label'   => __( 'Widget content', 'dicestack' ),
				'type'    => 'textarea',
				'default' => __( 'Need help with this site? Contact Dice Codes at Contact@dicecodes.com.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_dashboard_setup', array( $this, 'register' ) );
	}

	/**
	 * Register the widget.
	 *
	 * @return void
	 */
	public function register() {
		wp_add_dashboard_widget(
			'dicestack_welcome',
			esc_html( (string) $this->get_setting( 'title', __( 'Welcome', 'dicestack' ) ) ),
			array( $this, 'render' )
		);
	}

	/**
	 * Render the widget body.
	 *
	 * @return void
	 */
	public function render() {
		echo wpautop( wp_kses_post( (string) $this->get_setting( 'body', '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post sanitises.
	}
}
