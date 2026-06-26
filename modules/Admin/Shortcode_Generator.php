<?php
/**
 * Shortcode Generator module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Create reusable content snippets and expose each as a shortcode. Define a tag
 * and its HTML/text once, then drop [your_tag] anywhere. Great for addresses,
 * disclaimers, CTAs, tracking blocks, etc.
 */
final class Shortcode_Generator extends Abstract_Module {

	/**
	 * Option storing snippets (tag => content).
	 */
	const OPTION = 'dicestack_shortcodes';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'shortcode_generator';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Shortcode generator', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Turn any reusable text or HTML into your own [shortcode].', 'dicestack' );
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
			'php_memory_kb' => 22,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 1,
			'external_http' => 0,
		);
	}

	/**
	 * Read stored snippets.
	 *
	 * @return array<string,string>
	 */
	private function snippets() {
		$data = get_option( self::OPTION, array() );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		foreach ( $this->snippets() as $tag => $content ) {
			add_shortcode(
				$tag,
				function () use ( $content ) {
					return do_shortcode( wp_kses_post( $content ) );
				}
			);
		}

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_page' ) );
			add_action( 'admin_post_dicestack_save_shortcode', array( $this, 'handle_save' ) );
			add_action( 'admin_post_dicestack_delete_shortcode', array( $this, 'handle_delete' ) );
		}
	}

	/**
	 * Register the management page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'dicestack',
			__( 'Shortcodes', 'dicestack' ),
			__( 'Shortcodes', 'dicestack' ),
			'manage_options',
			'dicestack-shortcodes',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Save a snippet.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dicestack_shortcode' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}
		$tag     = isset( $_POST['tag'] ) ? sanitize_key( wp_unslash( $_POST['tag'] ) ) : '';
		$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		if ( '' !== $tag ) {
			$snips         = $this->snippets();
			$snips[ $tag ] = $content;
			update_option( self::OPTION, $snips );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=dicestack-shortcodes' ) );
		exit;
	}

	/**
	 * Delete a snippet.
	 *
	 * @return void
	 */
	public function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dicestack_shortcode' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}
		$tag   = isset( $_GET['tag'] ) ? sanitize_key( wp_unslash( $_GET['tag'] ) ) : '';
		$snips = $this->snippets();
		unset( $snips[ $tag ] );
		update_option( self::OPTION, $snips );
		wp_safe_redirect( admin_url( 'admin.php?page=dicestack-shortcodes' ) );
		exit;
	}

	/**
	 * Render the management page.
	 *
	 * @return void
	 */
	public function render_page() {
		$snips = $this->snippets();
		echo '<div class="wrap"><h1>' . esc_html__( 'Custom shortcodes', 'dicestack' ) . '</h1>';

		if ( $snips ) {
			echo '<table class="widefat striped" style="margin-bottom:20px;"><thead><tr><th>' . esc_html__( 'Shortcode', 'dicestack' ) . '</th><th>' . esc_html__( 'Preview', 'dicestack' ) . '</th><th></th></tr></thead><tbody>';
			foreach ( $snips as $tag => $content ) {
				$del = wp_nonce_url( admin_url( 'admin-post.php?action=dicestack_delete_shortcode&tag=' . rawurlencode( $tag ) ), 'dicestack_shortcode' );
				echo '<tr><td><code>[' . esc_html( $tag ) . ']</code></td><td>' . esc_html( wp_trim_words( wp_strip_all_tags( $content ), 12 ) ) . '</td><td><a href="' . esc_url( $del ) . '" class="button-link-delete">' . esc_html__( 'Delete', 'dicestack' ) . '</a></td></tr>';
			}
			echo '</tbody></table>';
		}

		echo '<h2>' . esc_html__( 'Add / update a shortcode', 'dicestack' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'dicestack_shortcode' );
		echo '<input type="hidden" name="action" value="dicestack_save_shortcode" />';
		echo '<p><label>' . esc_html__( 'Shortcode tag (letters, numbers, underscores)', 'dicestack' ) . '<br/><input type="text" name="tag" class="regular-text" required placeholder="my_snippet" /></label></p>';
		echo '<p><label>' . esc_html__( 'Content (text, HTML, or other shortcodes)', 'dicestack' ) . '<br/><textarea name="content" rows="6" class="large-text"></textarea></label></p>';
		echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Save shortcode', 'dicestack' ) . '</button></p>';
		echo '</form></div>';
	}
}
