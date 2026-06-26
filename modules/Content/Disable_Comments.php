<?php
/**
 * Disable Comments module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Turns comments off site-wide or per post type, and hides the comment UI in
 * wp-admin. Replaces the Disable Comments plugin.
 */
final class Disable_Comments extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'disable_comments';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Disable comments', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Switch off comments everywhere or just on pages and products.', 'dicestack' );
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
		return 'message-off';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 30,
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
				'key'     => 'scope',
				'label'   => __( 'Disable comments on', 'dicestack' ),
				'type'    => 'select',
				'default' => 'everywhere',
				'options' => array(
					'everywhere' => __( 'Everywhere', 'dicestack' ),
					'pages'      => __( 'Pages only', 'dicestack' ),
				),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'comments_open', array( $this, 'filter_open' ), 20, 2 );
		add_filter( 'pings_open', array( $this, 'filter_open' ), 20, 2 );
		add_filter( 'comments_array', array( $this, 'hide_existing' ), 20, 2 );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'remove_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'remove_dashboard_meta' ) );
		} else {
			add_action( 'wp_before_admin_bar_render', array( $this, 'remove_admin_bar' ) );
		}
	}

	/**
	 * Decide whether comments should be open for a given post.
	 *
	 * @param bool $open    Current state.
	 * @param int  $post_id Post ID.
	 * @return bool
	 */
	public function filter_open( $open, $post_id ) {
		$scope = $this->get_setting( 'scope', 'everywhere' );
		if ( 'everywhere' === $scope ) {
			return false;
		}
		if ( 'pages' === $scope && 'page' === get_post_type( $post_id ) ) {
			return false;
		}
		return $open;
	}

	/**
	 * Hide any already-approved comments when fully disabled.
	 *
	 * @param array $comments Comments.
	 * @param int   $post_id  Post ID.
	 * @return array
	 */
	public function hide_existing( $comments, $post_id ) {
		return 'everywhere' === $this->get_setting( 'scope', 'everywhere' ) ? array() : $comments;
	}

	/**
	 * Remove the Comments admin menu when fully disabled.
	 *
	 * @return void
	 */
	public function remove_admin_menu() {
		if ( 'everywhere' === $this->get_setting( 'scope', 'everywhere' ) ) {
			remove_menu_page( 'edit-comments.php' );
		}
	}

	/**
	 * Remove the dashboard "Recent Comments" widget.
	 *
	 * @return void
	 */
	public function remove_dashboard_meta() {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	/**
	 * Remove the admin-bar comments bubble.
	 *
	 * @return void
	 */
	public function remove_admin_bar() {
		global $wp_admin_bar;
		if ( $wp_admin_bar ) {
			$wp_admin_bar->remove_menu( 'comments' );
		}
	}
}
