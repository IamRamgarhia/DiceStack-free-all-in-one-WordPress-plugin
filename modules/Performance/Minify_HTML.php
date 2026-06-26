<?php
/**
 * Minify HTML module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Collapses redundant whitespace between HTML tags in the rendered page to
 * shave a few KB. Conservative: it leaves the contents of pre, textarea, script,
 * and style untouched.
 */
final class Minify_HTML extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'minify_html';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Minify HTML', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Strip needless whitespace from page HTML for a slightly smaller download.', 'dicestack' );
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
	public function replaces() {
		return 'premium caching plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 30,
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
		add_action( 'template_redirect', array( $this, 'start' ), 5 );
	}

	/**
	 * Begin buffering the output for minification.
	 *
	 * @return void
	 */
	public function start() {
		if ( is_admin() || is_feed() ) {
			return;
		}
		ob_start( array( $this, 'minify' ) );
	}

	/**
	 * Minify the buffered HTML, preserving sensitive blocks.
	 *
	 * @param string $html Page HTML.
	 * @return string
	 */
	public function minify( $html ) {
		if ( '' === trim( $html ) || stripos( $html, '<html' ) === false ) {
			return $html;
		}

		// Protect pre/textarea/script/style by tokenising them.
		$protected = array();
		$html      = preg_replace_callback(
			'#<(pre|textarea|script|style)\b[^>]*>.*?</\1>#is',
			function ( $m ) use ( &$protected ) {
				$key               = '%%DICESTACK' . count( $protected ) . '%%';
				$protected[ $key ] = $m[0];
				return $key;
			},
			$html
		);

		// Collapse whitespace between tags and runs of spaces.
		$html = preg_replace( '/>\s+</', '><', $html );
		$html = preg_replace( '/\s{2,}/', ' ', $html );

		// Restore protected blocks.
		if ( ! empty( $protected ) ) {
			$html = strtr( $html, $protected );
		}

		return $html;
	}
}
