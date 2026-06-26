<?php
/**
 * Post Views counter module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Counts post views locally (no external analytics) and can display the count.
 * Replaces Post Views Counter.
 */
final class Post_Views extends Abstract_Module {

	/**
	 * Meta key for the stored view count.
	 */
	const META = '_dicestack_views';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'post_views';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Post view counter', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Count and optionally display how many times each post has been viewed.', 'dicestack' );
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
		return 'search';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'view-counter plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 35,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 1,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function settings_schema() {
		return array(
			array(
				'key'     => 'display',
				'label'   => __( 'Show the view count under post content', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'count_admins',
				'label'   => __( 'Count views from logged-in admins', 'dicestack' ),
				'type'    => 'toggle',
				'default' => false,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp', array( $this, 'maybe_count' ) );
		if ( ! empty( $this->get_setting( 'display', true ) ) ) {
			add_filter( 'the_content', array( $this, 'append_count' ), 50 );
		}
		add_filter( 'manage_posts_columns', array( $this, 'add_column' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	/**
	 * Increment the count once per main single-post view.
	 *
	 * @return void
	 */
	public function maybe_count() {
		if ( is_admin() || ! is_singular( 'post' ) || ! is_main_query() ) {
			return;
		}
		if ( empty( $this->get_setting( 'count_admins', false ) ) && current_user_can( 'manage_options' ) ) {
			return;
		}
		$id    = get_queried_object_id();
		$views = (int) get_post_meta( $id, self::META, true );
		update_post_meta( $id, self::META, $views + 1 );
	}

	/**
	 * Append the count to the content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function append_count( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$views = (int) get_post_meta( get_the_ID(), self::META, true );
		$label = sprintf(
			/* translators: %s: number of views. */
			_n( '%s view', '%s views', $views, 'dicestack' ),
			number_format_i18n( $views )
		);
		return $content . '<p class="dicestack-views" style="color:#6b7280;font-size:13px;margin-top:16px;">' . esc_html( $label ) . '</p>';
	}

	/**
	 * Add a Views column to the posts list.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function add_column( $columns ) {
		$columns['dicestack_views'] = __( 'Views', 'dicestack' );
		return $columns;
	}

	/**
	 * Render the Views column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( $column, $post_id ) {
		if ( 'dicestack_views' === $column ) {
			echo esc_html( number_format_i18n( (int) get_post_meta( $post_id, self::META, true ) ) );
		}
	}
}
