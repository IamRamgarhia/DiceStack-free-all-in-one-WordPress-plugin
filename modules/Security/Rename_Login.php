<?php
/**
 * Rename Login URL module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Moves the login page from /wp-login.php to a secret slug of your choice and
 * makes the old /wp-login.php (and wp-admin for logged-out visitors) return a
 * 404 — a simple, effective block against automated login attacks. On change it
 * emails the new link to a chosen address so the client can save it.
 *
 * Fail-safe: with no slug set it does nothing, so it can never lock you out.
 */
final class Rename_Login extends Abstract_Module {

	/**
	 * Whether the current request is the (hidden) login page.
	 *
	 * @var bool
	 */
	private $serve_login = false;

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'rename_login';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Change login URL', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Move wp-login.php to a secret address and 404 the old one. Emails the new link to your client.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'security';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'shield-lock';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium hide-login plugins';
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
	public function settings_schema() {
		return array(
			array(
				'key'     => 'slug',
				'label'   => __( 'New login address', 'dicestack' ),
				'type'    => 'text',
				'default' => '',
				'help'    => __( 'e.g. "secret-door". Your login becomes yoursite.com/secret-door. Leave blank to keep the default. Avoid words like login, admin, wp-admin.', 'dicestack' ),
			),
			array(
				'key'     => 'notify',
				'label'   => __( 'Email the new link when it changes', 'dicestack' ),
				'type'    => 'checkbox',
				'default' => true,
			),
			array(
				'key'     => 'notify_email',
				'label'   => __( 'Send the link to', 'dicestack' ),
				'type'    => 'text',
				'default' => get_option( 'admin_email' ),
				'help'    => __( 'Where to email the new login link (e.g. your client). Defaults to the site admin email.', 'dicestack' ),
			),
		);
	}

	/**
	 * The configured login slug (sanitised, no slashes). Empty = disabled.
	 *
	 * @return string
	 */
	public function login_slug() {
		return trim( (string) $this->get_setting( 'slug', '' ), '/' );
	}

	/**
	 * The full new login URL.
	 *
	 * @param string|null $scheme URL scheme.
	 * @return string
	 */
	public function login_url( $scheme = null ) {
		$slug = $this->login_slug();
		if ( get_option( 'permalink_structure' ) ) {
			return user_trailingslashit( home_url( '/', $scheme ) . $slug );
		}
		return home_url( '/', $scheme ) . '?' . $slug;
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		// No slug = do nothing (can never lock anyone out).
		if ( '' === $this->login_slug() ) {
			return;
		}

		add_action( 'setup_theme', array( $this, 'detect' ), 1 );
		add_action( 'wp_loaded', array( $this, 'serve' ) );

		add_filter( 'site_url', array( $this, 'filter_url' ), 10, 1 );
		add_filter( 'network_site_url', array( $this, 'filter_url' ), 10, 1 );
		add_filter( 'wp_redirect', array( $this, 'filter_url' ), 10, 1 );
		add_filter( 'login_url', array( $this, 'filter_url' ), 10, 1 );
		add_filter( 'logout_url', array( $this, 'filter_url' ), 10, 1 );
		add_filter( 'lostpassword_url', array( $this, 'filter_url' ), 10, 1 );
		add_filter( 'register_url', array( $this, 'filter_url' ), 10, 1 );
	}

	/**
	 * The current request path, without a trailing slash.
	 *
	 * @return string
	 */
	private function current_path() {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		return untrailingslashit( $path );
	}

	/**
	 * Is this request for the hidden login slug?
	 *
	 * @return bool
	 */
	private function is_slug_request() {
		$slug = $this->login_slug();
		if ( get_option( 'permalink_structure' ) ) {
			$login = untrailingslashit( (string) wp_parse_url( home_url( $slug ), PHP_URL_PATH ) );
			return $this->current_path() === $login;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing check.
		return isset( $_GET[ $slug ] );
	}

	/**
	 * Mark slug requests so wp_loaded can serve the login page.
	 *
	 * @return void
	 */
	public function detect() {
		if ( $this->is_slug_request() ) {
			global $pagenow;
			$this->serve_login = true;
			$pagenow           = 'wp-login.php';
		}
	}

	/**
	 * Serve the hidden login, redirect logged-out wp-admin, and 404 the old URL.
	 *
	 * @return void
	 */
	public function serve() {
		// Logged-out visitors hitting wp-admin: send home (not to wp-login.php).
		$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) ) : '';
		if ( is_admin() && ! is_user_logged_in() && ! wp_doing_ajax() && 'admin-post.php' !== $script && 'admin-ajax.php' !== $script ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		if ( $this->serve_login ) {
			global $error, $interim_login, $action, $user_login, $user; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			require_once ABSPATH . 'wp-login.php';
			exit;
		}

		// Direct hit on the old wp-login.php → 404 (real links were rewritten).
		$path = $this->current_path();
		if ( false !== stripos( $path, 'wp-login.php' ) || 'wp-login.php' === $script ) {
			$this->not_found();
		}
	}

	/**
	 * Rewrite any wp-login.php URL to the hidden slug, preserving the query.
	 *
	 * @param string $url URL to filter.
	 * @return string
	 */
	public function filter_url( $url ) {
		if ( ! is_string( $url ) || false === strpos( $url, 'wp-login.php' ) ) {
			return $url;
		}
		$slug  = $this->login_slug();
		$query = '';
		if ( false !== ( $pos = strpos( $url, '?' ) ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			$query = substr( $url, $pos + 1 );
			$url   = substr( $url, 0, $pos );
		}
		if ( get_option( 'permalink_structure' ) ) {
			$url = str_replace( '/wp-login.php', '/' . $slug, $url );
			$url = user_trailingslashit( $url );
			return $query ? $url . '?' . $query : $url;
		}
		$url = str_replace( '/wp-login.php', '/', $url );
		$url = rtrim( $url, '/' ) . '/';
		return $query ? $url . '?' . $slug . '&' . $query : $url . '?' . $slug;
	}

	/**
	 * Send a 404 for the old login URL.
	 *
	 * @return void
	 */
	private function not_found() {
		status_header( 404 );
		nocache_headers();
		wp_die(
			esc_html__( 'This page does not exist.', 'dicestack' ),
			esc_html__( 'Not found', 'dicestack' ),
			array( 'response' => 404 )
		);
	}

	/**
	 * Persist settings, sanitising the slug, then email the new link if changed.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function save_settings( array $input ) {
		$old = $this->login_slug();

		// Sanitise the slug to a safe path segment.
		if ( isset( $input['slug'] ) ) {
			$input['slug'] = sanitize_title( $input['slug'] );
		}

		$clean = parent::save_settings( $input );
		$new   = trim( (string) ( isset( $clean['slug'] ) ? $clean['slug'] : '' ), '/' );

		if ( '' !== $new && $new !== $old && ! empty( $clean['notify'] ) ) {
			$to = isset( $clean['notify_email'] ) ? sanitize_email( $clean['notify_email'] ) : '';
			if ( ! is_email( $to ) ) {
				$to = get_option( 'admin_email' );
			}
			$this->send_email( $to );
		}

		return $clean;
	}

	/**
	 * Email the new login link to the client.
	 *
	 * @param string $to Recipient email.
	 * @return void
	 */
	private function send_email( $to ) {
		$site    = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$url     = $this->login_url();
		$subject = sprintf( /* translators: %s: site name. */ __( '[%s] Your new login link — please save it', 'dicestack' ), $site );
		$body    = sprintf(
			/* translators: 1: site name, 2: new login URL. */
			__(
				"Hello,\n\nFor security, the login address for %1\$s has been changed.\n\nYour NEW login link is:\n%2\$s\n\nPlease make a note of this link and keep it somewhere safe. The old /wp-login.php and /wp-admin login pages no longer work — you must use the link above to sign in.\n\n— DiceStack by Dice Codes",
				'dicestack'
			),
			$site,
			$url
		);
		wp_mail( $to, $subject, $body );
	}
}
