<?php
/**
 * External Link Indicator module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Accessibility;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds screen-reader text "(opens in a new tab)" to links that open in a new
 * window, so assistive-technology users aren't surprised by the new tab.
 */
final class External_Link_Indicator extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'external_link_indicator';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'New-tab link indicator', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Tell screen-reader users when a link opens in a new tab.', 'dicestack' );
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
		return 'external-link';
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
		add_filter( 'the_content', array( $this, 'process' ), 18 );
	}

	/**
	 * Append screen-reader text to target=_blank links.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function process( $content ) {
		if ( strpos( $content, 'target="_blank"' ) === false && strpos( $content, "target='_blank'" ) === false ) {
			return $content;
		}
		$sr = ' <span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'dicestack' ) . '</span>';

		return preg_replace_callback(
			'#(<a\b[^>]*target=("|\')_blank\2[^>]*>)(.*?)(</a>)#is',
			function ( $m ) use ( $sr ) {
				if ( false !== strpos( $m[3], 'screen-reader-text' ) ) {
					return $m[0];
				}
				return $m[1] . $m[3] . $sr . $m[4];
			},
			$content
		);
	}
}
