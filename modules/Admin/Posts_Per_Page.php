<?php
/**
 * Admin Posts Per Page module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Controls how many items show per page in admin list tables, so you scroll
 * less when managing lots of content.
 */
final class Posts_Per_Page extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'posts_per_page';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Admin items per page', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Set how many posts, pages, and products show per admin list page.', 'dicestack' );
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
	public function settings_schema() {
		return array(
			array(
				'key'     => 'per_page',
				'label'   => __( 'Items per page', 'dicestack' ),
				'type'    => 'number',
				'default' => 50,
				'min'     => 10,
				'max'     => 500,
				'step'    => 10,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		if ( ! is_admin() ) {
			return;
		}
		add_action( 'pre_get_posts', array( $this, 'apply' ) );
	}

	/**
	 * Apply the per-page count to admin list queries.
	 *
	 * @param \WP_Query $query Query.
	 * @return void
	 */
	public function apply( $query ) {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && 'edit' === $screen->base && $query->is_main_query() ) {
			$query->set( 'posts_per_page', (int) $this->get_setting( 'per_page', 50 ) );
		}
	}
}
