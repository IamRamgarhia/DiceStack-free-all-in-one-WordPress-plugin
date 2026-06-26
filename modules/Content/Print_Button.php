<?php
/**
 * Print Button module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a "Print this page" link to single posts (and a [dicestack_print] shortcode)
 * — handy for recipes, guides, and documentation.
 */
final class Print_Button extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'print_button';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Print button', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add a print link to posts, or place one anywhere with [dicestack_print].', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'content';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'file-code';
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
				'key'     => 'auto',
				'label'   => __( 'Show automatically on posts', 'dicestack' ),
				'type'    => 'toggle',
				'default' => false,
			),
			array(
				'key'     => 'label',
				'label'   => __( 'Button text', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'Print this page', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_shortcode( 'dicestack_print', array( $this, 'button' ) );
		if ( ! empty( $this->get_setting( 'auto', false ) ) ) {
			add_filter( 'the_content', array( $this, 'append' ), 48 );
		}
	}

	/**
	 * Render the print button HTML.
	 *
	 * @return string
	 */
	public function button() {
		$label = esc_html( (string) $this->get_setting( 'label', __( 'Print this page', 'dicestack' ) ) );
		return '<button type="button" class="dicestack-print" onclick="window.print()" style="background:#1b2a4a;color:#fff;border:0;padding:8px 16px;border-radius:6px;cursor:pointer;">&#128424; ' . $label . '</button>';
	}

	/**
	 * Append the button to single posts.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function append( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		return $content . '<p class="dicestack-print-wrap" style="margin-top:16px;">' . $this->button() . '</p>';
	}
}
