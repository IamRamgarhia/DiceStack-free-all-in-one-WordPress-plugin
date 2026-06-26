<?php
/**
 * Force HTTPS module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Redirects insecure HTTP requests to HTTPS. Only acts when the site address is
 * already configured as https, so it can't create a redirect loop on an
 * SSL-less site.
 */
final class Force_HTTPS extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'force_https';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Force HTTPS', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Send visitors to the secure https version of every page.', 'dicestack' );
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
		return 'lock';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium SSL plugins';
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
		add_action( 'template_redirect', array( $this, 'redirect' ), 1 );
	}

	/**
	 * Redirect to HTTPS when safe to do so.
	 *
	 * @return void
	 */
	public function redirect() {
		// Only force HTTPS if the configured home URL is already https.
		if ( 'https' !== wp_parse_url( home_url(), PHP_URL_SCHEME ) ) {
			return;
		}
		if ( is_ssl() ) {
			return;
		}
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( '' === $host ) {
			return;
		}
		wp_safe_redirect( 'https://' . $host . $uri, 301 );
		exit;
	}
}
