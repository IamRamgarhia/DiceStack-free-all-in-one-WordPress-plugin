<?php
/**
 * Search Term Highlight module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Highlights the visitor's search term in search-results titles and excerpts.
 */
final class Search_Highlight extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'search_highlight';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Search term highlight', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Highlight the searched words in your search results.', 'dicestack' );
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
		return 'search';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 16,
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
		// Only the excerpt on search results — never the_title (which also feeds
		// the <title> tag, nav menus, and admin lists).
		add_filter( 'the_excerpt', array( $this, 'highlight' ) );
	}

	/**
	 * Wrap matched terms in a mark element.
	 *
	 * @param string $text Text to process.
	 * @return string
	 */
	public function highlight( $text ) {
		if ( ! is_search() || is_admin() || ! is_main_query() ) {
			return $text;
		}
		$term = get_search_query();
		if ( '' === trim( $term ) ) {
			return $text;
		}
		$escaped = preg_quote( $term, '/' );
		return preg_replace(
			'/(' . $escaped . ')/iu',
			'<mark style="background:#fde68a;padding:0 2px;">$1</mark>',
			$text
		);
	}
}
