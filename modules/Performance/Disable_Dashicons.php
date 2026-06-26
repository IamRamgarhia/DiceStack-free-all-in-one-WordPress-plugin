<?php
/**
 * Disable front-end Dashicons module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the dashicons stylesheet on the front end for logged-out visitors,
 * where it is usually unused. Kept for logged-in users (the admin bar needs it).
 */
final class Disable_Dashicons extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'disable_dashicons';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Disable front-end Dashicons', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Skip loading the Dashicons stylesheet for logged-out visitors who do not need it.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'performance';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'bolt';
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
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue' ), 100 );
	}

	/**
	 * Dequeue dashicons for logged-out visitors only.
	 *
	 * @return void
	 */
	public function dequeue() {
		if ( ! is_user_logged_in() ) {
			wp_dequeue_style( 'dashicons' );
			wp_deregister_style( 'dashicons' );
		}
	}
}
