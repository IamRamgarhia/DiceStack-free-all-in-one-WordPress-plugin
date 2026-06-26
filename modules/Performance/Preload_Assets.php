<?php
/**
 * Preload Assets module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds <link rel="preload"> hints for critical fonts or CSS so the browser
 * fetches them sooner, improving render times.
 */
final class Preload_Assets extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'preload_assets';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Preload key assets', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Preload critical fonts or stylesheets so they load earlier.', 'dicestack' );
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
				'key'     => 'fonts',
				'label'   => __( 'Font URLs to preload', 'dicestack' ),
				'type'    => 'textarea',
				'default' => '',
				'help'    => __( 'One full .woff2 URL per line.', 'dicestack' ),
			),
			array(
				'key'     => 'styles',
				'label'   => __( 'Stylesheet URLs to preload', 'dicestack' ),
				'type'    => 'textarea',
				'default' => '',
				'help'    => __( 'One full .css URL per line.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_head', array( $this, 'output' ), 2 );
	}

	/**
	 * Print preload tags.
	 *
	 * @return void
	 */
	public function output() {
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $this->get_setting( 'fonts', '' ) ) as $url ) {
			$url = esc_url( trim( $url ) );
			if ( '' !== $url ) {
				echo '<link rel="preload" href="' . esc_url( $url ) . '" as="font" type="font/woff2" crossorigin />' . "\n";
			}
		}
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $this->get_setting( 'styles', '' ) ) as $url ) {
			$url = esc_url( trim( $url ) );
			if ( '' !== $url ) {
				echo '<link rel="preload" href="' . esc_url( $url ) . '" as="style" />' . "\n";
			}
		}
	}
}
