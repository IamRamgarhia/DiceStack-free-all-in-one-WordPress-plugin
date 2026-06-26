<?php
/**
 * Show ID Column module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds an ID column to posts, pages, and taxonomy list tables — handy when
 * building shortcodes and queries that reference IDs.
 */
final class Show_ID_Column extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'show_id_column';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Show ID column', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Display post, page, and category IDs in admin lists for easy reference.', 'dicestack' );
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
		return 'code';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 12,
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
	 * Append the ID column.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function add_column( $columns ) {
		$columns['dicestack_id'] = __( 'ID', 'dicestack' );
		return $columns;
	}

	/**
	 * Render the ID.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( $column, $post_id ) {
		if ( 'dicestack_id' === $column ) {
			echo (int) $post_id;
		}
	}
}
