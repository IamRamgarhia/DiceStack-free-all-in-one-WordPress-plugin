<?php
/**
 * Post Cloner module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a one-click "Clone" row action to duplicate any post, page, or custom
 * post type (including its meta). Replaces Duplicate Post.
 */
final class Post_Cloner extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'post_cloner';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Page & post cloner', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Duplicate any post, page, or product in one click — content and meta included.', 'dicestack' );
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
		return 'copy';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'post-duplication plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 25,
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
		add_filter( 'post_row_actions', array( $this, 'add_action_link' ), 10, 2 );
		add_filter( 'page_row_actions', array( $this, 'add_action_link' ), 10, 2 );
		add_action( 'admin_action_dicestack_clone', array( $this, 'handle_clone' ) );
	}

	/**
	 * Add the "Clone" link to the row actions.
	 *
	 * @param array    $actions Row actions.
	 * @param \WP_Post $post    Post object.
	 * @return array
	 */
	public function add_action_link( $actions, $post ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return $actions;
		}
		$url = wp_nonce_url(
			admin_url( 'admin.php?action=dicestack_clone&post=' . $post->ID ),
			'dicestack_clone_' . $post->ID
		);
		$actions['dicestack_clone'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Clone', 'dicestack' ) . '</a>';
		return $actions;
	}

	/**
	 * Perform the clone and redirect to the new draft.
	 *
	 * @return void
	 */
	public function handle_clone() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		if ( ! $post_id || ! check_admin_referer( 'dicestack_clone_' . $post_id ) ) {
			wp_die( esc_html__( 'Invalid clone request.', 'dicestack' ) );
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You are not allowed to clone content.', 'dicestack' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_die( esc_html__( 'Original not found.', 'dicestack' ) );
		}

		$new_id = wp_insert_post(
			array(
				'post_title'   => $post->post_title . ' ' . __( '(copy)', 'dicestack' ),
				'post_content' => $post->post_content,
				'post_excerpt' => $post->post_excerpt,
				'post_status'  => 'draft',
				'post_type'    => $post->post_type,
				'post_author'  => get_current_user_id(),
				'post_parent'  => $post->post_parent,
				'menu_order'   => $post->menu_order,
			),
			true
		);

		if ( is_wp_error( $new_id ) ) {
			wp_die( esc_html( $new_id->get_error_message() ) );
		}

		// Copy taxonomies.
		$taxonomies = get_object_taxonomies( $post->post_type );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $terms ) ) {
				wp_set_object_terms( $new_id, $terms, $taxonomy );
			}
		}

		// Copy meta (skip internal/protected unique keys).
		$meta = get_post_meta( $post_id );
		foreach ( $meta as $key => $values ) {
			if ( '_edit_lock' === $key || '_edit_last' === $key || '_wp_old_slug' === $key ) {
				continue;
			}
			foreach ( $values as $value ) {
				add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
			}
		}

		wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
		exit;
	}
}
