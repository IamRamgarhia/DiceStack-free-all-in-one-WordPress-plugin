<?php
/**
 * Login Protection module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Rate-limits failed logins per IP and locks out abusers. Replaces the core
 * brute-force protection sold in Wordfence / Loginizer Pro.
 */
final class Login_Protection extends Abstract_Module {

	/**
	 * Transient prefix for attempt counters.
	 */
	const PREFIX = 'dicestack_login_';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'login_protection';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Login protection', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Limit failed login attempts per IP and lock out brute-force bots.', 'dicestack' );
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
		return 'premium login-security plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 90,
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
				'key'     => 'max_attempts',
				'label'   => __( 'Max failed attempts before lockout', 'dicestack' ),
				'type'    => 'number',
				'default' => 5,
				'min'     => 2,
				'max'     => 20,
				'step'    => 1,
			),
			array(
				'key'     => 'lockout_minutes',
				'label'   => __( 'Lockout duration (minutes)', 'dicestack' ),
				'type'    => 'number',
				'default' => 15,
				'min'     => 1,
				'max'     => 1440,
				'step'    => 1,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'authenticate', array( $this, 'check_lockout' ), 30 );
		add_action( 'wp_login_failed', array( $this, 'record_failure' ) );
		add_action( 'wp_login', array( $this, 'clear_failures' ) );
	}

	/**
	 * Block authentication if the IP is currently locked out.
	 *
	 * @param \WP_User|\WP_Error|null $user Auth result so far.
	 * @return \WP_User|\WP_Error|null
	 */
	public function check_lockout( $user ) {
		$data = get_transient( $this->key() );
		if ( is_array( $data ) && isset( $data['locked_until'] ) && time() < $data['locked_until'] ) {
			$minutes = max( 1, (int) ceil( ( $data['locked_until'] - time() ) / 60 ) );
			return new \WP_Error(
				'dicestack_locked',
				sprintf(
					/* translators: %d: minutes remaining. */
					esc_html__( 'Too many failed attempts. Try again in %d minutes.', 'dicestack' ),
					$minutes
				)
			);
		}
		return $user;
	}

	/**
	 * Increment the failure counter and lock out if the threshold is hit.
	 *
	 * @return void
	 */
	public function record_failure() {
		$max     = (int) $this->get_setting( 'max_attempts', 5 );
		$minutes = (int) $this->get_setting( 'lockout_minutes', 15 );
		$data    = get_transient( $this->key() );
		$data    = is_array( $data ) ? $data : array( 'count' => 0 );

		$data['count'] = (int) $data['count'] + 1;

		if ( $data['count'] >= $max ) {
			$data['locked_until'] = time() + ( $minutes * MINUTE_IN_SECONDS );
		}

		set_transient( $this->key(), $data, $minutes * MINUTE_IN_SECONDS );
	}

	/**
	 * Reset the counter on a successful login.
	 *
	 * @return void
	 */
	public function clear_failures() {
		delete_transient( $this->key() );
	}

	/**
	 * Transient key for the current visitor's IP.
	 *
	 * @return string
	 */
	private function key() {
		return self::PREFIX . md5( $this->get_ip() );
	}

	/**
	 * Best-effort client IP detection.
	 *
	 * @return string
	 */
	private function get_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
	}
}
