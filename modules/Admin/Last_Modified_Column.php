<?php
/**
 * Last Modified Column module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a sortable "Last modified" column to the Posts and Pages list tables.
 */
final class Last_Modified_Column extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'last_modified_column';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Last modified column', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Show when each post and page was last edited, right in the list table.', 'dicestack' );
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
		return 'layout-grid';
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
	 * Add the column header.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function add_column( $columns ) {
		$columns['dicestack_modified'] = __( 'Last modified', 'dicestack' );
		return $columns;
	}

	/**
	 * Render the column value.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( $column, $post_id ) {
		if ( 'dicestack_modified' === $column ) {
			echo esc_html(
				sprintf(
					/* translators: %s: time diff. */
					__( '%s ago', 'dicestack' ),
					human_time_diff( (int) get_post_modified_time( 'U', true, $post_id ), time() )
				)
			);
		}
	}
}
