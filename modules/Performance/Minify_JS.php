<?php
/**
 * Minify JS module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Conservatively minifies inline and local JavaScript. String and template
 * literals are protected before comments and whitespace are removed, and line
 * breaks are kept so automatic semicolon insertion never changes behaviour.
 * External, admin, JSON-LD, template, and already-minified (.min.js) scripts
 * are left untouched. Local files are minified once and cached.
 */
final class Minify_JS extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'minify_js';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Minify JavaScript', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Strip comments and whitespace from inline and local scripts. Safe: strings are protected and line breaks kept.', 'dicestack' );
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
			'php_memory_kb' => 42,
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
				'label'   => __( 'Also minify linked script files', 'dicestack' ),
				'type'    => 'checkbox',
				'default' => true,
				'help'    => __( 'Minifies local .js files (cached). Turn off if a script misbehaves.', 'dicestack' ),
			),
			array(
				'key'     => 'inline',
				'label'   => __( 'Minify inline scripts', 'dicestack' ),
				'type'    => 'checkbox',
				'default' => true,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'template_redirect', array( $this, 'start' ), 7 );
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
	 * Process the page: minify inline and linked scripts.
	 *
	 * @param string $html Page HTML.
	 * @return string
	 */
	public function process( $html ) {
		if ( '' === trim( $html ) || stripos( $html, '<html' ) === false ) {
			return $html;
		}

		$html = preg_replace_callback( '#<script\b([^>]*)>(.*?)</script>#is', array( $this, 'handle_script' ), $html );

		return $html;
	}

	/**
	 * Decide what to do with a single <script> tag.
	 *
	 * @param array $m Regex match: [1] attributes, [2] body.
	 * @return string
	 */
	public function handle_script( $m ) {
		$attrs = $m[1];
		$body  = $m[2];

		// Only plain JavaScript: skip JSON-LD, templates, modules with odd types.
		if ( preg_match( '#type=([\'"])(.*?)\1#i', $attrs, $t ) ) {
			$type = strtolower( $t[2] );
			if ( '' !== $type && 'text/javascript' !== $type && 'application/javascript' !== $type && 'module' !== $type ) {
				return $m[0];
			}
		}

		// Linked file.
		if ( preg_match( '#src=([\'"])(.*?)\1#i', $attrs, $s ) ) {
			if ( ! $this->get_setting( 'files', true ) ) {
				return $m[0];
			}
			$src = $s[2];
			if ( false !== stripos( $src, '.min.js' ) ) {
				return $m[0];
			}
			$path = $this->url_to_path( $src );
			if ( ! $path || ! is_readable( $path ) || 'js' !== strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) ) {
				return $m[0];
			}
			$cached = $this->cache_file( $path );
			if ( ! $cached ) {
				return $m[0];
			}
			return '<script' . str_replace( $s[0], 'src=' . $s[1] . esc_url( $cached ) . $s[1], $attrs ) . '></script>';
		}

		// Inline script.
		if ( $this->get_setting( 'inline', true ) && '' !== trim( $body ) ) {
			return '<script' . $attrs . '>' . $this->minify( $body ) . '</script>';
		}

		return $m[0];
	}

	/**
	 * Conservatively minify JavaScript. Protects string/template literals first,
	 * removes block comments, trims trailing whitespace and blank lines, and
	 * keeps every remaining line break (ASI-safe).
	 *
	 * @param string $js Raw JavaScript.
	 * @return string
	 */
	public function minify( $js ) {
		$original = (string) $js;
		$store    = array();
		// Protect "..", '..', `..` (template literals may span lines).
		$protected = preg_replace_callback(
			'/("(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'|`(?:\\\\.|[^`\\\\])*`)/s',
			function ( $m ) use ( &$store ) {
				$k           = '__DICESTACKJS' . count( $store ) . '__';
				$store[ $k ] = $m[0];
				return $k;
			},
			$original
		);
		if ( null === $protected ) {
			return $original; // preg failed (e.g. backtrack limit): return untouched, never break the script.
		}

		// Remove block comments (keep /*! banners */).
		$js = preg_replace( '#/\*(?!\!).*?\*/#s', '', $protected );
		if ( null === $js ) {
			return $original;
		}

		// Trim trailing whitespace, drop blank lines (keep newlines).
		$lines = preg_split( '/\n/', $js );
		$out   = array();
		foreach ( $lines as $line ) {
			$line = rtrim( $line );
			if ( '' === trim( $line ) ) {
				continue;
			}
			$out[] = $line;
		}
		$js = implode( "\n", $out );

		// Restore protected literals.
		return strtr( $js, $store );
	}

	/**
	 * Map a script URL to a local filesystem path (same host only).
	 *
	 * @param string $url Script URL.
	 * @return string|false
	 */
	private function url_to_path( $url ) {
		$url = preg_replace( '/[?#].*$/', '', $url );
		if ( '' === $url ) {
			return false;
		}
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
	 * @param string $path Absolute path to the source JS file.
	 * @return string|false
	 */
	private function cache_file( $path ) {
		$uploads = wp_get_upload_dir();
		$dir     = trailingslashit( $uploads['basedir'] ) . 'dicestack-min/';
		$url_dir = trailingslashit( $uploads['baseurl'] ) . 'dicestack-min/';

		$key  = md5( $path . '|' . filemtime( $path ) ) . '.js';
		$file = $dir . $key;

		if ( ! is_readable( $file ) ) {
			if ( ! is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				@file_put_contents( $dir . 'index.html', '' );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a local script.
			$js = file_get_contents( $path );
			if ( false === $js ) {
				return false;
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			if ( false === @file_put_contents( $file, $this->minify( $js ), LOCK_EX ) ) {
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
		$files = glob( $dir . '*.js' );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				@unlink( $file );
			}
		}
	}
}
