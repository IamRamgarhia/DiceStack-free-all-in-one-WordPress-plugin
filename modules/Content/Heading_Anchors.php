<?php
/**
 * Heading Anchors module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds id attributes to post headings so they can be deep-linked. Pairs well
 * with the Table of Contents module.
 */
final class Heading_Anchors extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'heading_anchors';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Heading anchors', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add id anchors to headings so sections can be linked to directly.', 'dicestack' );
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
			'php_memory_kb' => 20,
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
		add_filter( 'the_content', array( $this, 'add_ids' ), 8 );
	}

	/**
	 * Add ids to headings missing them.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function add_ids( $content ) {
		if ( ! is_singular() || strpos( $content, '<h' ) === false ) {
			return $content;
		}
		$used = array();
		return preg_replace_callback(
			'/<h([2-4])([^>]*)>(.*?)<\/h\1>/is',
			function ( $m ) use ( &$used ) {
				if ( preg_match( '/\bid=/i', $m[2] ) ) {
					return $m[0];
				}
				$slug = sanitize_title( wp_strip_all_tags( $m[3] ) );
				if ( '' === $slug ) {
					return $m[0];
				}
				$base = $slug;
				$i    = 2;
				while ( isset( $used[ $slug ] ) ) {
					$slug = $base . '-' . $i;
					$i++;
				}
				$used[ $slug ] = true;
				return '<h' . $m[1] . $m[2] . ' id="' . esc_attr( $slug ) . '">' . $m[3] . '</h' . $m[1] . '>';
			},
			$content
		);
	}
}
