<?php
/**
 * Site Verification module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the verification meta tags for Google Search Console, Bing, Pinterest,
 * and Yandex without editing theme files.
 */
final class Site_Verification extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'site_verification';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Site verification', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add Google, Bing, Pinterest, and Yandex verification meta tags.', 'dicestack' );
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
		return 'search';
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
				'key'     => 'google',
				'label'   => __( 'Google verification code', 'dicestack' ),
				'type'    => 'text',
				'default' => '',
				'help'    => __( 'The content value from the google-site-verification meta tag.', 'dicestack' ),
				'guide'   => array(
					'url'   => 'https://search.google.com/search-console',
					'label' => __( 'Get this from Google Search Console', 'dicestack' ),
				),
			),
			array(
				'key'     => 'bing',
				'label'   => __( 'Bing verification code', 'dicestack' ),
				'type'    => 'text',
				'default' => '',
			),
			array(
				'key'     => 'pinterest',
				'label'   => __( 'Pinterest verification code', 'dicestack' ),
				'type'    => 'text',
				'default' => '',
			),
			array(
				'key'     => 'yandex',
				'label'   => __( 'Yandex verification code', 'dicestack' ),
				'type'    => 'text',
				'default' => '',
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_head', array( $this, 'output' ), 1 );
	}

	/**
	 * Print verification meta tags.
	 *
	 * @return void
	 */
	public function output() {
		$map = array(
			'google'    => 'google-site-verification',
			'bing'      => 'msvalidate.01',
			'pinterest' => 'p:domain_verify',
			'yandex'    => 'yandex-verification',
		);
		foreach ( $map as $key => $name ) {
			$code = trim( (string) $this->get_setting( $key, '' ) );
			if ( '' !== $code ) {
				echo '<meta name="' . esc_attr( $name ) . '" content="' . esc_attr( $code ) . '" />' . "\n";
			}
		}
	}
}
