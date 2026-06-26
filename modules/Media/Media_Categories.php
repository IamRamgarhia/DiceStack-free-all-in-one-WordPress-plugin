<?php
/**
 * Media Categories module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Media;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a category taxonomy to the Media Library so uploads can be organised and
 * filtered — a lightweight take on media folders.
 */
final class Media_Categories extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'media_categories';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Media categories', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Organise the Media Library with categories you can filter by.', 'dicestack' );
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
	public function replaces() {
		return 'premium media-folder plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 28,
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
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Register a "media_category" taxonomy on attachments.
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		register_taxonomy(
			'dicestack_media_category',
			'attachment',
			array(
				'labels'       => array(
					'name'          => __( 'Media categories', 'dicestack' ),
					'singular_name' => __( 'Media category', 'dicestack' ),
					'menu_name'     => __( 'Media categories', 'dicestack' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_admin_column' => true,
				'hierarchical' => true,
				'rewrite'      => false,
			)
		);
	}
}
