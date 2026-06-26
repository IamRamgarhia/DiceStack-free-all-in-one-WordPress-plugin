<?php
/**
 * Widget Shortcodes module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Enables shortcode processing inside classic text widgets.
 */
final class Widget_Shortcodes extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'widget_shortcodes';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Shortcodes in widgets', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Allow shortcodes to run inside classic text widgets.', 'dicestack' );
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
		return 'forms';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 10,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 0,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'widget_text', 'do_shortcode', 11 );
	}
}
