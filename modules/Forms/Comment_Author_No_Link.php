<?php
/**
 * Comment Author No Link module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Forms;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Strips the hyperlink from commenter names so the comment author URL can't be
 * used for link-building spam.
 */
final class Comment_Author_No_Link extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'comment_author_no_link';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Unlink comment authors', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Remove the website link from commenter names to discourage spam.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'forms';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'message-off';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 8,
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
		add_filter( 'get_comment_author_link', array( $this, 'unlink' ) );
		add_filter( 'comment_author_url', '__return_empty_string' );
	}

	/**
	 * Return the plain author name without a link.
	 *
	 * @param string $link Author link HTML.
	 * @return string
	 */
	public function unlink( $link ) {
		// Strip anchor tags, keep the name text.
		return wp_strip_all_tags( $link );
	}
}
