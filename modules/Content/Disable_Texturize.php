<?php
/**
 * Disable Texturize module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Turns off wptexturize, which converts straight quotes/dashes to "smart"
 * versions. Useful for code-heavy or technical blogs where that mangles content.
 */
final class Disable_Texturize extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'disable_texturize';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Disable smart punctuation', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Stop WordPress converting straight quotes and dashes to "smart" characters.', 'dicestack' );
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
		return 'code';
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
		remove_filter( 'the_content', 'wptexturize' );
		remove_filter( 'the_title', 'wptexturize' );
		remove_filter( 'comment_text', 'wptexturize' );
		add_filter( 'run_wptexturize', '__return_false' );
	}
}
