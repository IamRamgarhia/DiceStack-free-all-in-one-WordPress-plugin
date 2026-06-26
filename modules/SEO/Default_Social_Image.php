<?php
/**
 * Default Social Image module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Provides a fallback Open Graph / Twitter image for pages without a featured
 * image, so shared links always show a branded preview.
 */
final class Default_Social_Image extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'default_social_image';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Default social image', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Show a fallback preview image when a page has no featured image for sharing.', 'dicestack' );
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
		return 'photo';
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
				'key'     => 'image',
				'label'   => __( 'Fallback image URL', 'dicestack' ),
				'type'    => 'url',
				'default' => '',
				'help'    => __( 'Recommended size: 1200×630 pixels.', 'dicestack' ),
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
	 * Output fallback OG/Twitter image tags when no featured image exists.
	 *
	 * @return void
	 */
	public function output() {
		$image = esc_url( (string) $this->get_setting( 'image', '' ) );
		if ( '' === $image ) {
			return;
		}
		// If a singular page already has a featured image, the Meta Tags module
		// handles it — only add the fallback when there isn't one.
		if ( is_singular() && has_post_thumbnail() ) {
			return;
		}
		echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	}
}
