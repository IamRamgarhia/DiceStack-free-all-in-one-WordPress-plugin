<?php
/**
 * Responsive Tables module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Wraps content tables in a horizontally scrollable container so wide tables
 * don't break the layout on small screens.
 */
final class Responsive_Tables extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'responsive_tables';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Responsive tables', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Make wide content tables scroll horizontally on mobile instead of overflowing.', 'dicestack' );
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
		return 'layout-grid';
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
		add_filter( 'the_content', array( $this, 'wrap' ), 20 );
	}

	/**
	 * Wrap each table in a scroll container.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function wrap( $content ) {
		if ( strpos( $content, '<table' ) === false ) {
			return $content;
		}
		return preg_replace(
			'/<table(.*?)<\/table>/is',
			'<div class="dicestack-table-wrap" style="overflow-x:auto;-webkit-overflow-scrolling:touch;"><table$1</table></div>',
			$content
		);
	}
}
