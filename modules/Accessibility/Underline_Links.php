<?php
/**
 * Underline Content Links module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Accessibility;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Forces underlines on in-content links so they're distinguishable from body
 * text by more than colour alone — a WCAG recommendation.
 */
final class Underline_Links extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'underline_links';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Underline content links', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Underline links within content so they\'re distinguishable without relying on colour.', 'dicestack' );
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
	public function init() {
		add_action( 'wp_head', array( $this, 'output' ), 99 );
	}

	/**
	 * Print the underline styles, scoped to common content containers.
	 *
	 * @return void
	 */
	public function output() {
		echo '<style id="dicestack-underline">.entry-content a,.post-content a,article .content a,main p a{text-decoration:underline;}</style>';
	}
}
