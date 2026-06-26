<?php
/**
 * Auto Featured Image module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * When a post is saved without a featured image, sets the first image in its
 * content as the featured image.
 */
final class Auto_Featured_Image extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'auto_featured_image';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Auto featured image', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Use the first image in a post as its featured image when none is set.', 'dicestack' );
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
			'php_memory_kb' => 22,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 0,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'save_post', array( $this, 'maybe_set' ), 20, 2 );
	}

	/**
	 * Set the featured image from the first content image if missing.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function maybe_set( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || has_post_thumbnail( $post_id ) ) {
			return;
		}
		if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( preg_match( '/wp-image-(\d+)/i', (string) $post->post_content, $m ) ) {
			$attachment_id = absint( $m[1] );
			if ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) {
				set_post_thumbnail( $post_id, $attachment_id );
			}
		}
	}
}
