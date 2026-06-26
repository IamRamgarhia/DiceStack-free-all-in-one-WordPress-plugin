<?php
/**
 * Security Hardening module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * A checklist of well-known WordPress hardening steps, each individually
 * toggleable. Replaces the hardening portion of iThemes/Solid Security.
 */
final class Security_Hardening extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'security_hardening';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Security hardening', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Hide the WP version, add security headers, block the file editor, and more.', 'dicestack' );
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
		return 'shield';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium security plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 60,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 1,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function settings_schema() {
		return array(
			array(
				'key'     => 'hide_version',
				'label'   => __( 'Hide WordPress version', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Removes the version meta tag and query strings that reveal your WP version.', 'dicestack' ),
			),
			array(
				'key'     => 'disable_file_editor',
				'label'   => __( 'Disable the theme/plugin file editor', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Stops admins editing PHP from wp-admin — a common post-breach attack path.', 'dicestack' ),
			),
			array(
				'key'     => 'security_headers',
				'label'   => __( 'Send security headers', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Adds X-Frame-Options, X-Content-Type-Options, and Referrer-Policy headers.', 'dicestack' ),
			),
			array(
				'key'     => 'disable_user_enum',
				'label'   => __( 'Block user enumeration', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
				'help'    => __( 'Prevents ?author=1 scans from revealing usernames.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		$s = $this->get_settings();

		if ( ! empty( $s['hide_version'] ) ) {
			remove_action( 'wp_head', 'wp_generator' );
			add_filter( 'the_generator', '__return_empty_string' );
			add_filter( 'style_loader_src', array( $this, 'strip_version_query' ), 9999 );
			add_filter( 'script_loader_src', array( $this, 'strip_version_query' ), 9999 );
		}

		if ( ! empty( $s['disable_file_editor'] ) && ! defined( 'DISALLOW_FILE_EDIT' ) ) {
			define( 'DISALLOW_FILE_EDIT', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- DISALLOW_FILE_EDIT is a WordPress core constant.
		}

		if ( ! empty( $s['security_headers'] ) ) {
			add_action( 'send_headers', array( $this, 'send_security_headers' ) );
		}

		if ( ! empty( $s['disable_user_enum'] ) ) {
			add_action( 'init', array( $this, 'block_user_enumeration' ) );
		}
	}

	/**
	 * Strip the ?ver= query from core asset URLs.
	 *
	 * @param string $src Asset URL.
	 * @return string
	 */
	public function strip_version_query( $src ) {
		if ( $src && strpos( $src, 'ver=' ) !== false ) {
			$src = remove_query_arg( 'ver', $src );
		}
		return $src;
	}

	/**
	 * Output security response headers.
	 *
	 * @return void
	 */
	public function send_security_headers() {
		if ( headers_sent() ) {
			return;
		}
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	}

	/**
	 * Redirect author enumeration attempts on the front end.
	 *
	 * @return void
	 */
	public function block_user_enumeration() {
		if ( is_admin() ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only scan detection, no state change.
		if ( isset( $_GET['author'] ) && ! empty( $_GET['author'] ) ) {
			wp_safe_redirect( home_url(), 301 );
			exit;
		}
	}
}
