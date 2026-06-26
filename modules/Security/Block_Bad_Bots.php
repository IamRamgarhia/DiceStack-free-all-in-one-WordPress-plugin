<?php
/**
 * Block Bad Bots module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Blocks requests from known malicious user agents (vulnerability scanners,
 * aggressive scrapers) before WordPress finishes loading.
 */
final class Block_Bad_Bots extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'block_bad_bots';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Block bad bots', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Reject known vulnerability scanners and aggressive scrapers by user agent.', 'dicestack' );
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
			'php_memory_kb' => 25,
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
				'key'     => 'extra',
				'label'   => __( 'Extra blocked user-agent keywords', 'dicestack' ),
				'type'    => 'textarea',
				'default' => '',
				'help'    => __( 'One keyword per line, matched case-insensitively against the user agent.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'init', array( $this, 'maybe_block' ), 0 );
	}

	/**
	 * Block the request if its user agent matches the blocklist.
	 *
	 * @return void
	 */
	public function maybe_block() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
		if ( '' === $ua ) {
			return;
		}

		$blocked = array( 'sqlmap', 'nikto', 'nessus', 'masscan', 'nmap', 'fimap', 'havij', 'acunetix', 'wpscan', 'zgrab' );

		$extra = (string) $this->get_setting( 'extra', '' );
		foreach ( preg_split( '/\r\n|\r|\n/', $extra ) as $line ) {
			$line = strtolower( trim( $line ) );
			if ( '' !== $line ) {
				$blocked[] = $line;
			}
		}

		foreach ( $blocked as $needle ) {
			if ( strpos( $ua, $needle ) !== false ) {
				status_header( 403 );
				nocache_headers();
				wp_die( esc_html__( 'Access denied.', 'dicestack' ), '', array( 'response' => 403 ) );
			}
		}
	}
}
