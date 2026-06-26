<?php
/**
 * Redirect Manager module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Simple 301/302 redirect manager. Rules are entered one per line as
 * "/old-path => /new-path". Replaces the core of the Redirection plugin.
 */
final class Redirect_Manager extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'redirect_manager';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Redirect manager', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Create 301/302 redirects to fix broken URLs and preserve SEO.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'seo';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'arrow-back-up';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium redirect plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 40,
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
				'key'     => 'type',
				'label'   => __( 'Redirect type', 'dicestack' ),
				'type'    => 'select',
				'default' => '301',
				'options' => array(
					'301' => __( '301 — permanent', 'dicestack' ),
					'302' => __( '302 — temporary', 'dicestack' ),
				),
			),
			array(
				'key'     => 'rules',
				'label'   => __( 'Redirect rules', 'dicestack' ),
				'type'    => 'textarea',
				'default' => '',
				'help'    => __( 'One per line: /old-path => /new-path (or a full URL).', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 1 );
	}

	/**
	 * Check the current request path against the rules and redirect if matched.
	 *
	 * @return void
	 */
	public function maybe_redirect() {
		$rules = $this->parse_rules();
		if ( empty( $rules ) ) {
			return;
		}

		$request = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		$request = untrailingslashit( $request );

		foreach ( $rules as $from => $to ) {
			if ( untrailingslashit( $from ) === $request ) {
				$status = '302' === $this->get_setting( 'type', '301' ) ? 302 : 301;
				$target = ( 0 === strpos( $to, 'http' ) ) ? $to : home_url( $to );
				wp_safe_redirect( $target, $status );
				exit;
			}
		}
	}

	/**
	 * Parse the textarea rules into a from => to map.
	 *
	 * @return array<string,string>
	 */
	private function parse_rules() {
		$raw = (string) $this->get_setting( 'rules', '' );
		if ( '' === trim( $raw ) ) {
			return array();
		}

		$map   = array();
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line || strpos( $line, '=>' ) === false ) {
				continue;
			}
			list( $from, $to ) = array_map( 'trim', explode( '=>', $line, 2 ) );
			if ( '' !== $from && '' !== $to ) {
				$map[ $from ] = $to;
			}
		}
		return $map;
	}
}
