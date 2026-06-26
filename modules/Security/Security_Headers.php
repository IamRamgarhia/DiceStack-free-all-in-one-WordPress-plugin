<?php
/**
 * Advanced Security Headers module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Sends additional HTTP security headers (HSTS, Permissions-Policy, COOP) beyond
 * the basics in Security Hardening. HSTS is only sent over HTTPS to avoid
 * locking out an http site.
 */
final class Security_Headers extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'security_headers';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Advanced security headers', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Send HSTS, Permissions-Policy, and other modern hardening headers.', 'dicestack' );
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
	public function performance_profile() {
		return array(
			'php_memory_kb' => 14,
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
				'key'     => 'hsts',
				'label'   => __( 'Strict-Transport-Security (HSTS) — HTTPS only', 'dicestack' ),
				'type'    => 'toggle',
				'default' => false,
				'help'    => __( 'Only enable once HTTPS works everywhere; browsers will refuse http for a year.', 'dicestack' ),
			),
			array(
				'key'     => 'permissions_policy',
				'label'   => __( 'Permissions-Policy (limit camera/mic/geolocation)', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'coop',
				'label'   => __( 'Cross-Origin-Opener-Policy', 'dicestack' ),
				'type'    => 'toggle',
				'default' => false,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'send_headers', array( $this, 'send' ) );
	}

	/**
	 * Send the configured headers.
	 *
	 * @return void
	 */
	public function send() {
		if ( headers_sent() ) {
			return;
		}
		if ( ! empty( $this->get_setting( 'hsts', false ) ) && is_ssl() ) {
			header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
		}
		if ( ! empty( $this->get_setting( 'permissions_policy', true ) ) ) {
			header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );
		}
		if ( ! empty( $this->get_setting( 'coop', false ) ) ) {
			header( 'Cross-Origin-Opener-Policy: same-origin' );
		}
	}
}
