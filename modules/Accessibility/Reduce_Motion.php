<?php
/**
 * Reduce Motion module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Accessibility;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Honours the visitor's "prefers-reduced-motion" setting by neutralising CSS
 * animations and transitions — important for users with vestibular disorders.
 */
final class Reduce_Motion extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'reduce_motion';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Respect reduced motion', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Disable animations for visitors who prefer reduced motion in their OS settings.', 'dicestack' );
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
			'php_memory_kb' => 8,
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
	 * Print the reduced-motion stylesheet.
	 *
	 * @return void
	 */
	public function output() {
		echo '<style id="dicestack-reduce-motion">@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.001ms!important;animation-iteration-count:1!important;transition-duration:.001ms!important;scroll-behavior:auto!important;}}</style>';
	}
}
