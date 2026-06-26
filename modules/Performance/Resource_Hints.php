<?php
/**
 * Resource Hints (preconnect) module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds preconnect / dns-prefetch hints for third-party origins (fonts, CDNs,
 * analytics) so the browser opens those connections earlier.
 */
final class Resource_Hints extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'resource_hints';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Preconnect & DNS prefetch', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Open connections to fonts, CDNs, and analytics earlier for faster loads.', 'dicestack' );
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
		return 'premium performance plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 18,
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
				'key'     => 'origins',
				'label'   => __( 'Origins to preconnect', 'dicestack' ),
				'type'    => 'textarea',
				'default' => "https://fonts.googleapis.com\nhttps://fonts.gstatic.com",
				'help'    => __( 'One origin per line, e.g. https://fonts.gstatic.com', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'wp_resource_hints', array( $this, 'add_hints' ), 10, 2 );
	}

	/**
	 * Add the configured origins as preconnect hints.
	 *
	 * @param array  $urls          Current hint URLs.
	 * @param string $relation_type Relation type.
	 * @return array
	 */
	public function add_hints( $urls, $relation_type ) {
		if ( 'preconnect' !== $relation_type ) {
			return $urls;
		}
		$origins = (string) $this->get_setting( 'origins', '' );
		foreach ( preg_split( '/\r\n|\r|\n/', $origins ) as $origin ) {
			$origin = esc_url_raw( trim( $origin ) );
			if ( '' !== $origin ) {
				$urls[] = array(
					'href'        => $origin,
					'crossorigin' => 'anonymous',
				);
			}
		}
		return $urls;
	}
}
