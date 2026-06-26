<?php
/**
 * Social Profiles module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Outputs your social profile URLs as Organization sameAs JSON-LD so search
 * engines connect your brand to its social accounts.
 */
final class Social_Profiles extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'social_profiles';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Social profiles', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Tell search engines about your social accounts with sameAs structured data.', 'dicestack' );
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
		return 'world';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 20,
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
				'key'     => 'profiles',
				'label'   => __( 'Social profile URLs', 'dicestack' ),
				'type'    => 'textarea',
				'default' => '',
				'help'    => __( 'One full URL per line (Facebook, X, LinkedIn, Instagram, etc.).', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_head', array( $this, 'output' ), 6 );
	}

	/**
	 * Output the sameAs graph on the front page only.
	 *
	 * @return void
	 */
	public function output() {
		if ( ! is_front_page() ) {
			return;
		}
		$urls = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $this->get_setting( 'profiles', '' ) ) as $line ) {
			$line = esc_url_raw( trim( $line ) );
			if ( '' !== $line ) {
				$urls[] = $line;
			}
		}
		if ( empty( $urls ) ) {
			return;
		}

		$data = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'name'     => get_bloginfo( 'name' ),
			'url'      => home_url( '/' ),
			'sameAs'   => $urls,
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $data ) . '</script>' . "\n";
	}
}
