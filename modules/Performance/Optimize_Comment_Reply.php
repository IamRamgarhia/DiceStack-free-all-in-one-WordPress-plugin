<?php
/**
 * Optimize Comment Reply Script module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * WordPress loads comment-reply.js on every page that allows comments, even
 * where it isn't needed. This loads it only on singular pages with open,
 * threaded comments.
 */
final class Optimize_Comment_Reply extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'optimize_comment_reply';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Optimize comment-reply script', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Only load the threaded-comments script where it is actually needed.', 'dicestack' );
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
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue' ), 100 );
	}

	/**
	 * Dequeue comment-reply.js unless this is a singular view with threaded,
	 * open comments.
	 *
	 * @return void
	 */
	public function maybe_dequeue() {
		$needed = is_singular() && comments_open() && (bool) get_option( 'thread_comments' );
		if ( ! $needed ) {
			wp_dequeue_script( 'comment-reply' );
		}
	}
}
