<?php
/**
 * Focus Outline module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Accessibility;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Forces a clearly visible keyboard focus outline on interactive elements —
 * many themes hide it, which fails WCAG focus-visible requirements.
 */
final class Focus_Outline extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'focus_outline';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Visible focus outline', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Show a clear keyboard focus outline on links, buttons, and fields for accessibility.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'accessibility';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'accessible';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 10,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0.2,
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
				'key'     => 'color',
				'label'   => __( 'Outline colour', 'dicestack' ),
				'type'    => 'color',
				'default' => '#0aa2c0',
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_head', array( $this, 'output' ), 99 );
	}

	/**
	 * Print the focus styles.
	 *
	 * @return void
	 */
	public function output() {
		$color = sanitize_hex_color( (string) $this->get_setting( 'color', '#0aa2c0' ) ) ?: '#0aa2c0';
		echo '<style id="dicestack-focus">a:focus-visible,button:focus-visible,input:focus-visible,select:focus-visible,textarea:focus-visible,[tabindex]:focus-visible{outline:3px solid ' . esc_attr( $color ) . '!important;outline-offset:2px!important;}</style>';
	}
}
