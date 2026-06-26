<?php
/**
 * Default Image Link module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Media;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Sets the default "Link To" for newly inserted images to None, so editors stop
 * accidentally linking every image to its attachment page.
 */
final class Default_Image_Link extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'default_image_link';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Default image link: none', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Stop new images defaulting to a link, avoiding accidental attachment-page links.', 'dicestack' );
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
			'php_memory_kb' => 10,
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
		add_action( 'admin_init', array( $this, 'set_default' ) );
	}

	/**
	 * Force the image default link option to none.
	 *
	 * @return void
	 */
	public function set_default() {
		if ( 'none' !== get_option( 'image_default_link_type' ) ) {
			update_option( 'image_default_link_type', 'none' );
		}
	}
}
