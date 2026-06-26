<?php
/**
 * Featured Image Column module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Shows a thumbnail of each post/page's featured image in the admin list table.
 */
final class Featured_Image_Column extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'featured_image_column';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Featured image column', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Preview featured images directly in the posts and pages list.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'admin';
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
			'php_memory_kb' => 16,
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
		if ( ! is_admin() ) {
			return;
		}
		foreach ( array( 'post', 'page' ) as $type ) {
			add_filter( "manage_{$type}_posts_columns", array( $this, 'add_column' ) );
			add_action( "manage_{$type}_posts_custom_column", array( $this, 'render_column' ), 10, 2 );
		}
	}

	/**
	 * Add the thumbnail column near the front.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function add_column( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['dicestack_thumb'] = __( 'Image', 'dicestack' );
			}
			$new[ $key ] = $label;
		}
		return $new;
	}

	/**
	 * Render the thumbnail.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( $column, $post_id ) {
		if ( 'dicestack_thumb' !== $column ) {
			return;
		}
		if ( has_post_thumbnail( $post_id ) ) {
			echo get_the_post_thumbnail( $post_id, array( 48, 48 ), array( 'style' => 'border-radius:4px;object-fit:cover;' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-generated img.
		} else {
			echo '<span style="color:#ccc;">—</span>';
		}
	}
}
