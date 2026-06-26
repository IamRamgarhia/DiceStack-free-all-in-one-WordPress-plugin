<?php
/**
 * Image Title & Alt module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a title attribute to content images that have alt text but no title,
 * improving accessibility and image SEO consistency.
 */
final class Image_Title_Alt extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'image_title_alt';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Image title from alt', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Fill in missing image title attributes from their alt text in content.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'seo';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'photo';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 18,
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
		add_filter( 'the_content', array( $this, 'process' ), 12 );
	}

	/**
	 * Add title attributes derived from alt where missing.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function process( $content ) {
		if ( strpos( $content, '<img' ) === false ) {
			return $content;
		}
		return preg_replace_callback(
			'/<img\b[^>]*>/i',
			function ( $m ) {
				$tag = $m[0];
				if ( preg_match( '/\btitle=/i', $tag ) ) {
					return $tag;
				}
				if ( preg_match( '/\balt=("|\')(.*?)\1/i', $tag, $alt ) && '' !== trim( $alt[2] ) ) {
					$title = esc_attr( $alt[2] );
					return preg_replace( '/<img\b/i', '<img title="' . $title . '"', $tag, 1 );
				}
				return $tag;
			},
			$content
		);
	}
}
