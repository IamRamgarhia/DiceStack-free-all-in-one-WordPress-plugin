<?php
/**
 * Page Cache module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * A safe, conservative full-page HTML cache. It stores the rendered HTML for
 * logged-out GET requests and serves it early on the next visit, skipping theme
 * and query rendering. It deliberately never caches logged-in users, POSTs,
 * query-string URLs, feeds, REST, or WooCommerce cart/checkout/account pages.
 * Replaces the page-cache core of WP Rocket / WP Super Cache.
 */
final class Page_Cache extends Abstract_Module {

	/**
	 * Absolute path to the cache directory.
	 *
	 * @var string
	 */
	private $dir;

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'page_cache';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Page caching', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Serve cached HTML to logged-out visitors for much faster page loads.', 'dicestack' );
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
			'php_memory_kb' => 70,
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
				'key'     => 'lifetime_hours',
				'label'   => __( 'Cache lifetime (hours)', 'dicestack' ),
				'type'    => 'number',
				'default' => 10,
				'min'     => 1,
				'max'     => 720,
				'step'    => 1,
			),
			array(
				'key'     => 'exclude',
				'label'   => __( 'Exclude URLs containing', 'dicestack' ),
				'type'    => 'textarea',
				'default' => '',
				'help'    => __( 'One fragment per line, e.g. /my-account. Cart, checkout, and account pages are always excluded.', 'dicestack' ),
			),
			array(
				'key'     => 'gzip',
				'label'   => __( 'Gzip compress cached pages', 'dicestack' ),
				'type'    => 'checkbox',
				'default' => true,
				'help'    => __( 'Serve a pre-compressed copy to browsers that support it (smaller, faster).', 'dicestack' ),
			),
			array(
				'key'     => 'preload',
				'label'   => __( 'Preload cache after clearing', 'dicestack' ),
				'type'    => 'checkbox',
				'default' => false,
				'help'    => __( 'After the cache is cleared, DiceStack quietly re-visits your home page and recent posts/pages so the next visitor always gets a cached (fast) page.', 'dicestack' ),
			),
			array(
				'key'     => 'mobile_cache',
				'label'   => __( 'Separate cache for mobile', 'dicestack' ),
				'type'    => 'checkbox',
				'default' => false,
				'help'    => __( 'Store a separate copy for phones (turn on if your theme serves different mobile markup).', 'dicestack' ),
			),
			array(
				'key'     => 'exclude_cookies',
				'label'   => __( 'Never cache when these cookies are set', 'dicestack' ),
				'type'    => 'textarea',
				'default' => '',
				'help'    => __( 'One cookie-name fragment per line.', 'dicestack' ),
			),
			array(
				'key'     => 'exclude_agents',
				'label'   => __( 'Never cache these user agents', 'dicestack' ),
				'type'    => 'textarea',
				'default' => '',
				'help'    => __( 'One user-agent fragment per line, e.g. bot, crawler.', 'dicestack' ),
			),
		);
	}

	/**
	 * Resolve (and create) the cache directory.
	 *
	 * @return string
	 */
	private function cache_dir() {
		if ( ! $this->dir ) {
			$uploads   = wp_get_upload_dir();
			$this->dir = trailingslashit( $uploads['basedir'] ) . 'dicestack-cache/';
		}
		return $this->dir;
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		// Try to serve from cache as early as is safe.
		add_action( 'template_redirect', array( $this, 'serve' ), 0 );

		// Invalidate on content changes.
		foreach ( array( 'save_post', 'deleted_post', 'comment_post', 'switch_theme', 'customize_save_after', 'wp_update_nav_menu' ) as $hook ) {
			add_action( $hook, array( $this, 'clear' ) );
		}

		// Warm the cache again after it is cleared (cron).
		add_action( 'dicestack_cache_preload', array( $this, 'preload' ) );

		// Clean up when the module is switched off or settings change.
		add_action( 'dicestack_module_disabled_' . $this->id(), array( $this, 'on_disable' ) );
	}

	/**
	 * Clean up on disable: purge cache and cancel any pending preload.
	 *
	 * @return void
	 */
	public function on_disable() {
		$this->clear();
		wp_clear_scheduled_hook( 'dicestack_cache_preload' );
	}

	/**
	 * URLs to warm: home page plus the most recently modified posts/pages.
	 *
	 * @return string[]
	 */
	private function preload_urls() {
		$urls  = array( home_url( '/' ) );
		$posts = get_posts(
			array(
				'post_type'        => array( 'post', 'page' ),
				'post_status'      => 'publish',
				'numberposts'      => 40,
				'fields'           => 'ids',
				'orderby'          => 'modified',
				'order'            => 'DESC',
			)
		);
		foreach ( $posts as $pid ) {
			$url = get_permalink( $pid );
			if ( $url ) {
				$urls[] = $url;
			}
		}
		return array_values( array_unique( $urls ) );
	}

	/**
	 * Re-visit key URLs (non-blocking) so they are cached for the next visitor.
	 *
	 * @return void
	 */
	public function preload() {
		if ( ! $this->get_setting( 'preload', false ) ) {
			return;
		}
		foreach ( $this->preload_urls() as $url ) {
			wp_remote_get(
				$url,
				array(
					'blocking'   => false,
					'timeout'    => 1,
					'sslverify'  => false,
					'user-agent' => 'DiceStack-Preload',
				)
			);
		}
	}

	/**
	 * Decide whether the current request is cacheable.
	 *
	 * @return bool
	 */
	private function is_cacheable() {
		if ( is_admin() || is_user_logged_in() ) {
			return false;
		}
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return false;
		}
		if ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) {
			return false;
		}
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
		if ( 'GET' !== $method ) {
			return false;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only checking presence of query args.
		if ( ! empty( $_GET ) ) {
			return false;
		}
		if ( is_feed() || is_preview() || is_404() || is_search() ) {
			return false;
		}

		// Never cache WooCommerce dynamic pages.
		if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
			return false;
		}
		// Skip if a WooCommerce cart session cookie is present.
		foreach ( array_keys( $_COOKIE ) as $cookie ) {
			if ( 0 === strpos( $cookie, 'woocommerce_items_in_cart' ) || 0 === strpos( $cookie, 'wp_woocommerce_session' ) || 0 === strpos( $cookie, 'comment_author' ) ) {
				return false;
			}
		}

		// User-defined URL exclusions.
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $this->get_setting( 'exclude', '' ) ) as $frag ) {
			$frag = trim( $frag );
			if ( '' !== $frag && false !== strpos( $uri, $frag ) ) {
				return false;
			}
		}

		// User-defined cookie exclusions.
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $this->get_setting( 'exclude_cookies', '' ) ) as $frag ) {
			$frag = trim( $frag );
			if ( '' === $frag ) {
				continue;
			}
			foreach ( array_keys( $_COOKIE ) as $cookie ) {
				if ( false !== strpos( $cookie, $frag ) ) {
					return false;
				}
			}
		}

		// User-defined user-agent exclusions.
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
		if ( '' !== $agent ) {
			foreach ( preg_split( '/\r\n|\r|\n/', (string) $this->get_setting( 'exclude_agents', '' ) ) as $frag ) {
				$frag = strtolower( trim( $frag ) );
				if ( '' !== $frag && false !== strpos( $agent, $frag ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Cache file path for the current request.
	 *
	 * @return string
	 */
	private function current_file( $gz = false ) {
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$mobile = ( $this->get_setting( 'mobile_cache', false ) && function_exists( 'wp_is_mobile' ) && wp_is_mobile() ) ? 'm' : '';
		return $this->cache_dir() . md5( $host . $uri . $mobile ) . ( $gz ? '.html.gz' : '.html' );
	}

	/**
	 * Whether the visitor accepts gzip and gzip caching is on.
	 *
	 * @return bool
	 */
	private function gzip_ok() {
		if ( ! $this->get_setting( 'gzip', true ) || ! function_exists( 'gzencode' ) ) {
			return false;
		}
		$accept = isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) : '';
		return false !== strpos( $accept, 'gzip' );
	}

	/**
	 * Serve a cached page if a fresh copy exists; otherwise begin buffering.
	 *
	 * @return void
	 */
	public function serve() {
		if ( ! $this->is_cacheable() ) {
			return;
		}

		$lifetime = (int) $this->get_setting( 'lifetime_hours', 10 ) * HOUR_IN_SECONDS;

		// Prefer a pre-compressed copy when the browser supports gzip.
		if ( $this->gzip_ok() ) {
			$gz = $this->current_file( true );
			if ( is_readable( $gz ) && ( time() - filemtime( $gz ) ) < $lifetime ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading our own cache file.
				$data = file_get_contents( $gz );
				if ( false !== $data ) {
					header( 'X-DiceStack-Cache: HIT' );
					header( 'Content-Encoding: gzip' );
					header( 'Vary: Accept-Encoding' );
					echo $data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- previously-rendered, gzipped page.
					exit;
				}
			}
		}

		$file = $this->current_file();
		if ( is_readable( $file ) && ( time() - filemtime( $file ) ) < $lifetime ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading our own cache file.
			$html = file_get_contents( $file );
			if ( false !== $html ) {
				header( 'X-DiceStack-Cache: HIT' );
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- previously-rendered page HTML.
				exit;
			}
		}

		// No fresh cache: buffer this render and store it.
		ob_start( array( $this, 'store' ) );
	}

	/**
	 * Output-buffer callback: persist the rendered HTML, then return it.
	 *
	 * @param string $html Buffered page HTML.
	 * @return string
	 */
	public function store( $html ) {
		// Only store full, successful HTML documents.
		if ( strlen( $html ) < 255 || stripos( $html, '</html>' ) === false ) {
			return $html;
		}
		if ( ! $this->is_cacheable() ) {
			return $html;
		}

		$dir = $this->cache_dir();
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
			// Stop directory listing.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			@file_put_contents( $dir . 'index.html', '' );
		}

		$output = $html . "\n<!-- cached by DiceStack -->";

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		@file_put_contents( $this->current_file(), $output, LOCK_EX );

		// Also store a gzip copy for compression-capable browsers.
		if ( $this->get_setting( 'gzip', true ) && function_exists( 'gzencode' ) ) {
			$gz = gzencode( $output, 6 );
			if ( false !== $gz ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				@file_put_contents( $this->current_file( true ), $gz, LOCK_EX );
			}
		}

		return $output;
	}

	/**
	 * Delete all cached pages.
	 *
	 * @return void
	 */
	public function clear() {
		$dir = $this->cache_dir();
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$files = glob( $dir . '*.html*' );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				if ( 'index.html' !== basename( $file ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
					@unlink( $file );
				}
			}
		}

		// Schedule a single preload run shortly after (if enabled and not queued).
		if ( $this->get_setting( 'preload', false ) && ! wp_next_scheduled( 'dicestack_cache_preload' ) ) {
			wp_schedule_single_event( time() + 120, 'dicestack_cache_preload' );
		}
	}
}
