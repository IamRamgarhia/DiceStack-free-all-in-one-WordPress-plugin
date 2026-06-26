<?php
/**
 * YouTube Privacy Embed module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Rewrites YouTube embeds to the privacy-enhanced youtube-nocookie.com domain,
 * reducing tracking before consent — helpful for GDPR compliance.
 */
final class YouTube_NoCookie extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'youtube_nocookie';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'YouTube privacy mode', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Load YouTube embeds from the no-cookie domain to reduce tracking.', 'dicestack' );
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
		return 'world';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 14,
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
		add_filter( 'the_content', array( $this, 'rewrite' ), 25 );
		add_filter( 'embed_oembed_html', array( $this, 'rewrite' ), 10 );
	}

	/**
	 * Swap youtube.com embeds for youtube-nocookie.com.
	 *
	 * @param string $html Content/embed HTML.
	 * @return string
	 */
	public function rewrite( $html ) {
		if ( ! is_string( $html ) || strpos( $html, 'youtube.com/embed' ) === false ) {
			return $html;
		}
		return str_replace(
			array( 'https://www.youtube.com/embed', 'http://www.youtube.com/embed', 'https://youtube.com/embed' ),
			'https://www.youtube-nocookie.com/embed',
			$html
		);
	}
}
