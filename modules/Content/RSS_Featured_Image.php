<?php
/**
 * RSS Featured Image module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the post's featured image to RSS feed items so feed readers and email
 * services (Mailchimp RSS campaigns) can display it.
 */
final class RSS_Featured_Image extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'rss_featured_image';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Featured image in RSS', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Include the featured image at the top of each RSS feed item.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'content';
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
				'key'     => 'size',
				'label'   => __( 'Image size', 'dicestack' ),
				'type'    => 'select',
				'default' => 'medium',
				'options' => array(
					'thumbnail' => __( 'Thumbnail', 'dicestack' ),
					'medium'    => __( 'Medium', 'dicestack' ),
					'large'     => __( 'Large', 'dicestack' ),
				),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'the_excerpt_rss', array( $this, 'prepend' ) );
		add_filter( 'the_content_feed', array( $this, 'prepend' ) );
	}

	/**
	 * Prepend the featured image to feed content.
	 *
	 * @param string $content Feed item content.
	 * @return string
	 */
	public function prepend( $content ) {
		if ( ! has_post_thumbnail() ) {
			return $content;
		}
		$size = (string) $this->get_setting( 'size', 'medium' );
		$img  = get_the_post_thumbnail( get_the_ID(), $size, array( 'style' => 'max-width:100%;height:auto;margin-bottom:12px;' ) );
		return $img . $content;
	}
}
