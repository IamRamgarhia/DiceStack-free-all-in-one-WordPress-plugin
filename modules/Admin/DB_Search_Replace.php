<?php
/**
 * Database Search & Replace module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Safe search-and-replace across post content, custom fields, and options —
 * essential after a site migration or domain change. Handles serialized data and
 * offers a dry-run preview before changing anything.
 */
final class DB_Search_Replace extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'db_search_replace';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Database search & replace', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Find and replace text across content, fields, and options — safe with serialized data.', 'dicestack' );
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
		return 'database';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium search-replace tools';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 35,
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
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_post_dicestack_sr_run', array( $this, 'handle_run' ) );
	}

	/**
	 * Register the page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'dicestack',
			__( 'Search & replace', 'dicestack' ),
			__( 'Search & replace', 'dicestack' ),
			'manage_options',
			'dicestack-search-replace',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Recursively replace within strings, arrays, and objects.
	 *
	 * @param mixed  $data    Data.
	 * @param string $search  Search.
	 * @param string $replace Replace.
	 * @param int    $count   Running match count (by reference).
	 * @return mixed
	 */
	private function deep_replace( $data, $search, $replace, &$count ) {
		if ( is_string( $data ) ) {
			$n = substr_count( $data, $search );
			if ( $n > 0 ) {
				$count += $n;
				$data   = str_replace( $search, $replace, $data );
			}
			return $data;
		}
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				$data[ $k ] = $this->deep_replace( $v, $search, $replace, $count );
			}
			return $data;
		}
		if ( is_object( $data ) ) {
			foreach ( get_object_vars( $data ) as $k => $v ) {
				$data->$k = $this->deep_replace( $v, $search, $replace, $count );
			}
			return $data;
		}
		return $data;
	}

	/**
	 * Run the search/replace.
	 *
	 * @param string $search  Search string.
	 * @param string $replace Replacement.
	 * @param bool   $dry     Dry run (count only).
	 * @return int Number of replacements made (or that would be made).
	 */
	private function run( $search, $replace, $dry ) {
		global $wpdb;
		$count = 0;

		// Posts.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$posts = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_content, post_title, post_excerpt FROM {$wpdb->posts} WHERE post_content LIKE %s OR post_title LIKE %s OR post_excerpt LIKE %s", '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' ) );
		foreach ( (array) $posts as $p ) {
			$new_content = $this->deep_replace( $p->post_content, $search, $replace, $count );
			$new_title   = $this->deep_replace( $p->post_title, $search, $replace, $count );
			$new_excerpt = $this->deep_replace( $p->post_excerpt, $search, $replace, $count );
			if ( ! $dry ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( $wpdb->posts, array( 'post_content' => $new_content, 'post_title' => $new_title, 'post_excerpt' => $new_excerpt ), array( 'ID' => $p->ID ) );
			}
		}

		// Post meta (serialized-safe).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- one-off admin-triggered search/replace.
		$meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE %s", '%' . $wpdb->esc_like( $search ) . '%' ) );
		foreach ( (array) $meta as $m ) {
			$value = maybe_unserialize( $m->meta_value );
			$value = $this->deep_replace( $value, $search, $replace, $count );
			if ( ! $dry ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- one-off admin-triggered search/replace.
				$wpdb->update( $wpdb->postmeta, array( 'meta_value' => maybe_serialize( $value ) ), array( 'meta_id' => $m->meta_id ) );
			}
		}

		// Options (serialized-safe).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$options = $wpdb->get_results( $wpdb->prepare( "SELECT option_id, option_value FROM {$wpdb->options} WHERE option_value LIKE %s", '%' . $wpdb->esc_like( $search ) . '%' ) );
		foreach ( (array) $options as $o ) {
			$value = maybe_unserialize( $o->option_value );
			$value = $this->deep_replace( $value, $search, $replace, $count );
			if ( ! $dry ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( $wpdb->options, array( 'option_value' => maybe_serialize( $value ) ), array( 'option_id' => $o->option_id ) );
			}
		}

		if ( ! $dry ) {
			wp_cache_flush();
		}
		return $count;
	}

	/**
	 * Handle the form.
	 *
	 * @return void
	 */
	public function handle_run() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dicestack_sr' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}
		$search  = isset( $_POST['search'] ) ? (string) wp_unslash( $_POST['search'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- exact-match search needle.
		$replace = isset( $_POST['replace'] ) ? (string) wp_unslash( $_POST['replace'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- exact replacement value.
		$dry     = ! isset( $_POST['apply'] );

		$count = ( '' !== $search ) ? $this->run( $search, $replace, $dry ) : 0;
		set_transient( 'dicestack_sr_result', array( 'count' => $count, 'dry' => $dry ), 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=dicestack-search-replace' ) );
		exit;
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public function render_page() {
		$result = get_transient( 'dicestack_sr_result' );
		delete_transient( 'dicestack_sr_result' );

		echo '<div class="wrap"><h1>' . esc_html__( 'Database search & replace', 'dicestack' ) . '</h1>';
		echo '<p style="color:#a32d2d;">' . esc_html__( 'Always back up before applying changes. Use dry run first.', 'dicestack' ) . '</p>';

		if ( is_array( $result ) ) {
			$msg = $result['dry']
				? sprintf( /* translators: %d: count. */ __( 'Dry run: %d replacements would be made.', 'dicestack' ), $result['count'] )
				: sprintf( /* translators: %d: count. */ __( 'Done: %d replacements made.', 'dicestack' ), $result['count'] );
			echo '<div class="notice notice-info"><p>' . esc_html( $msg ) . '</p></div>';
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'dicestack_sr' );
		echo '<input type="hidden" name="action" value="dicestack_sr_run" />';
		echo '<table class="form-table">';
		echo '<tr><th>' . esc_html__( 'Search for', 'dicestack' ) . '</th><td><input type="text" name="search" class="regular-text" required placeholder="https://old-domain.com" /></td></tr>';
		echo '<tr><th>' . esc_html__( 'Replace with', 'dicestack' ) . '</th><td><input type="text" name="replace" class="regular-text" placeholder="https://new-domain.com" /></td></tr>';
		echo '</table>';
		echo '<p><button type="submit" class="button">' . esc_html__( 'Dry run (preview)', 'dicestack' ) . '</button> ';
		echo '<button type="submit" name="apply" value="1" class="button button-primary" onclick="return confirm(\'' . esc_js( __( 'Apply replacements to the database now?', 'dicestack' ) ) . '\');">' . esc_html__( 'Apply changes', 'dicestack' ) . '</button></p>';
		echo '</form></div>';
	}
}
