<?php
/**
 * Media Alt Text module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Media;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Automatically fills in image alt text from the filename on upload when none
 * is set — better accessibility and image SEO with zero effort.
 */
final class Media_Alt_Text extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'media_alt_text';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Auto image alt text', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Generate alt text from the filename on upload for better accessibility and SEO.', 'dicestack' );
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
	public function init() {
		add_action( 'add_attachment', array( $this, 'set_alt' ) );
	}

	/**
	 * Set alt text from the filename if it's an image and alt is empty.
	 *
	 * @param int $post_id Attachment ID.
	 * @return void
	 */
	public function set_alt( $post_id ) {
		if ( ! wp_attachment_is_image( $post_id ) ) {
			return;
		}
		$existing = get_post_meta( $post_id, '_wp_attachment_image_alt', true );
		if ( ! empty( $existing ) ) {
			return;
		}

		$file = get_post_meta( $post_id, '_wp_attached_file', true );
		$name = pathinfo( (string) $file, PATHINFO_FILENAME );
		// Turn "my-cool_image-01" into "My cool image 01".
		$alt = trim( preg_replace( '/[-_]+/', ' ', $name ) );
		$alt = trim( preg_replace( '/\s+\d+$/', '', $alt ) ); // drop trailing counter.
		$alt = ucfirst( $alt );

		if ( '' !== $alt ) {
			update_post_meta( $post_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
		}
	}
}
