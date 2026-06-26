<?php
/**
 * Image Sizes Control module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Media;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Stops WordPress generating selected default thumbnail sizes (and the 1536/2048
 * "big image" sizes) to save disk space and speed up uploads.
 */
final class Image_Sizes_Control extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'image_sizes_control';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Image sizes control', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Stop generating unused thumbnail sizes to save disk space on every upload.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'media';
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
			'php_memory_kb' => 15,
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
				'key'     => 'medium_large',
				'label'   => __( 'Disable the "medium_large" (768px) size', 'dicestack' ),
				'type'    => 'toggle',
				'default' => false,
			),
			array(
				'key'     => 'disable_scaled',
				'label'   => __( 'Disable the extra 2560px "scaled" image', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		if ( ! empty( $this->get_setting( 'medium_large', false ) ) ) {
			add_filter( 'intermediate_image_sizes_advanced', array( $this, 'remove_sizes' ) );
		}
		if ( ! empty( $this->get_setting( 'disable_scaled', true ) ) ) {
			add_filter( 'big_image_size_threshold', '__return_false' );
		}
	}

	/**
	 * Remove the medium_large size from generation.
	 *
	 * @param array $sizes Image sizes to be generated.
	 * @return array
	 */
	public function remove_sizes( $sizes ) {
		unset( $sizes['medium_large'] );
		return $sizes;
	}
}
