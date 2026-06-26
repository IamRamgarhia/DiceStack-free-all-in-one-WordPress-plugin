<?php
/**
 * Minify CSS module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Minifies inline <style> blocks and local enqueued stylesheets. Linked local
 * CSS files are minified once and cached in uploads/dicestack-min/, with relative
 * url() references rewritten to absolute so nothing breaks when the file moves.
 * External, admin, and already-minified (.min.css) files are left alone.
 */
final class Minify_CSS extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'minify_css';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Minify CSS', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Shrink inline and local stylesheet CSS by stripping comments and whitespace. Cached for speed.', 'dicestack' );
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
			'php_memory_kb' => 40,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 0,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function settings_schema() {
		return array(
			array(
				'key'     => 'files',
				'label'   => __( 'Also minify linked stylesheet files', 'dicestack' ),
				'type'    => 'checkbox',
				'default' => true,
				'help'    => __( 'Minifies local .css files (cached). Turn off if a stylesheet misbehaves.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'template_redirect', array( $this, 'start' ), 6 );
		add_action( 'dicestack_module_disabled_' . $this->id(), array( $this, 'clear_cache' ) );
		add_action( 'switch_theme', array( $this, 'clear_cache' ) );
	}

	/**
	 * Begin buffering the front-end output.
	 *
	 * @return void
	 */
	public function start() {
		if ( is_admin() || is_feed() ) {
			return;
		}
		ob_start( array( $this, 'process' ) );
	}

	/**
	 * Process the page: minify inline styles and (optionally) linked files.
	 *
	 * @param string $html Page HTML.
	 * @return string
	 */
	public function process( $html ) {
		if ( '' === trim( $html ) || stripos( $html, '<html' ) === false ) {
			return $html;
		}

		// Inline <style> blocks.
		$html = preg_replace_callback(
			'#<style\b([^>]*)>(.*?)</style>#is',
			function ( $m ) {
				$min = $this->minify( $m[2] );
				return '<style' . $m[1] . '>' . $min . '</style>';
			},
			$html
		);

		// Linked local stylesheets.
		if ( $this->get_setting( 'files', true ) ) {
			$html = preg_replace_callback( '#<link\b[^>]*>#i', array( $this, 'maybe_link' ), $html );
		}

		return $html;
	}

	/**
	 * Rewrite a <link> tag to its minified copy when it is a local stylesheet.
	 *
	 * @param array $m Regex match (full tag in [0]).
	 * @return string
	 */
	public function maybe_link( $m ) {
		$tag = $m[0];
		if ( false === stripos( $tag, 'stylesheet' ) || ! preg_match( '#href=([\'"])(.*?)\1#i', $tag, $h ) ) {
			return $tag;
		}
		$href = $h[2];
		if ( false !== stripos( $href, '.min.css' ) ) {
			return $tag; // Already minified.
		}

		$path = $this->url_to_path( $href );
		if ( ! $path || ! is_readable( $path ) || 'css' !== strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) ) {
			return $tag;
		}

		$cached_url = $this->cache_file( $path, $href );
		if ( ! $cached_url ) {
			return $tag;
		}

		return str_replace( $h[0], 'href=' . $h[1] . esc_url( $cached_url ) . $h[1], $tag );
	}

	/**
	 * Minify a CSS string (comment + whitespace removal). Safe for CSS.
	 *
	 * @param string $css Raw CSS.
	 * @return string
	 */
	public function minify( $css ) {
		// Strip comments (keep /*! important banners */).
		$css = preg_replace( '#/\*(?!\!).*?\*/#s', '', (string) $css );
		// Collapse whitespace.
		$css = preg_replace( '/\s+/', ' ', $css );
		// Trim space around structural symbols.
		$css = preg_replace( '/\s*([{}:;,>~+])\s*/', '$1', $css );
		// Drop the last semicolon in a block.
		$css = str_replace( ';}', '}', $css );
		return trim( (string) $css );
	}

	/**
	 * Rewrite relative url() references to absolute, based on the file's URL dir.
	 *
	 * @param string $css      CSS contents.
	 * @param string $base_url Directory URL of the original file (trailing slash).
	 * @return string
	 */
	private function absolutize( $css, $base_url ) {
		return preg_replace_callback(
			'/url\(\s*([\'"]?)([^\'")]+)\1\s*\)/i',
			function ( $u ) use ( $base_url ) {
				$ref = trim( $u[2] );
				if ( '' === $ref || preg_match( '#^(data:|https?:|//|/|\#)#i', $ref ) ) {
					return 'url(' . $u[1] . $ref . $u[1] . ')';
				}
				return 'url(' . $u[1] . $base_url . $ref . $u[1] . ')';
			},
			$css
		);
	}

	/**
	 * Map a stylesheet URL to a local filesystem path (same host only).
	 *
	 * @param string $url Stylesheet URL.
	 * @return string|false
	 */
	private function url_to_path( $url ) {
		$url = preg_replace( '/[?#].*$/', '', $url ); // Drop query/hash.
		if ( '' === $url ) {
			return false;
		}
		// Make protocol-relative / absolute consistent.
		if ( 0 === strpos( $url, '//' ) ) {
			$url = ( is_ssl() ? 'https:' : 'http:' ) . $url;
		}

		$maps = array(
			array( content_url(), WP_CONTENT_DIR ),
			array( includes_url(), ABSPATH . WPINC ),
			array( site_url(), untrailingslashit( ABSPATH ) ),
		);
		foreach ( $maps as $map ) {
			list( $base_url, $base_dir ) = $map;
			if ( 0 === strpos( $url, $base_url ) ) {
				$rel  = ltrim( substr( $url, strlen( $base_url ) ), '/' );
				$path = wp_normalize_path( $base_dir . '/' . $rel );
				// Guard against traversal outside the mapped base.
				if ( 0 === strpos( $path, wp_normalize_path( $base_dir ) ) ) {
					return $path;
				}
			}
		}
		return false;
	}

	/**
	 * Build (or reuse) a cached minified copy and return its URL.
	 *
	 * @param string $path Absolute path to the source CSS file.
	 * @param string $href Original href (used to derive the base URL).
	 * @return string|false
	 */
	private function cache_file( $path, $href ) {
		$uploads = wp_get_upload_dir();
		$dir     = trailingslashit( $uploads['basedir'] ) . 'dicestack-min/';
		$url_dir = trailingslashit( $uploads['baseurl'] ) . 'dicestack-min/';

		$key  = md5( $path . '|' . filemtime( $path ) ) . '.css';
		$file = $dir . $key;

		if ( ! is_readable( $file ) ) {
			if ( ! is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				@file_put_contents( $dir . 'index.html', '' );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a local stylesheet.
			$css = file_get_contents( $path );
			if ( false === $css ) {
				return false;
			}
			$base_url = trailingslashit( dirname( preg_replace( '/[?#].*$/', '', $href ) ) );
			$css      = $this->absolutize( $this->minify( $css ), $base_url );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			if ( false === @file_put_contents( $file, $css, LOCK_EX ) ) {
				return false;
			}
		}

		return $url_dir . $key . '?v=' . filemtime( $path );
	}

	/**
	 * Remove all cached minified files.
	 *
	 * @return void
	 */
	public function clear_cache() {
		$uploads = wp_get_upload_dir();
		$dir     = trailingslashit( $uploads['basedir'] ) . 'dicestack-min/';
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$files = glob( $dir . '*.css' );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				@unlink( $file );
			}
		}
	}
}
