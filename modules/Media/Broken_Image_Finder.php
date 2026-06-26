<?php
/**
 * Broken Image Finder module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Media;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Scans post content for local image URLs whose files are missing on disk —
 * finding broken images without any slow external HTTP checks.
 */
final class Broken_Image_Finder extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'broken_image_finder';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Broken image finder', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Find images in your content whose files are missing, so you can fix them.', 'dicestack' );
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
	public function init() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_page' ) );
		}
	}

	/**
	 * Register the page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'dicestack',
			__( 'Broken images', 'dicestack' ),
			__( 'Broken images', 'dicestack' ),
			'manage_options',
			'dicestack-broken-images',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Scan published content for missing local images.
	 *
	 * @return array[] Each: post_id, title, edit, url.
	 */
	private function scan() {
		$uploads = wp_get_upload_dir();
		$baseurl = $uploads['baseurl'];
		$basedir = $uploads['basedir'];
		$found   = array();

		$posts = get_posts(
			array(
				'post_type'   => array( 'post', 'page' ),
				'post_status' => 'publish',
				'numberposts' => 300,
			)
		);

		foreach ( (array) $posts as $post ) {
			if ( ! preg_match_all( '/<img[^>]+src=("|\')([^"\']+)\1/i', (string) $post->post_content, $m ) ) {
				continue;
			}
			foreach ( $m[2] as $src ) {
				if ( false === strpos( $src, $baseurl ) ) {
					continue; // only check local uploads.
				}
				$rel  = str_replace( $baseurl, '', $src );
				$path = $basedir . $rel;
				if ( ! file_exists( $path ) ) {
					$found[] = array(
						'title' => get_the_title( $post ),
						'edit'  => get_edit_post_link( $post->ID, '' ),
						'url'   => $src,
					);
				}
			}
		}
		return $found;
	}

	/**
	 * Render the results.
	 *
	 * @return void
	 */
	public function render_page() {
		$broken = $this->scan();
		echo '<div class="wrap"><h1>' . esc_html__( 'Broken images', 'dicestack' ) . '</h1>';
		if ( empty( $broken ) ) {
			echo '<p>' . esc_html__( 'No broken local images found in your recent content.', 'dicestack' ) . '</p></div>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Post', 'dicestack' ) . '</th><th>' . esc_html__( 'Missing image', 'dicestack' ) . '</th></tr></thead><tbody>';
		foreach ( $broken as $b ) {
			echo '<tr><td><a href="' . esc_url( $b['edit'] ) . '">' . esc_html( $b['title'] ) . '</a></td><td><code style="font-size:11px;">' . esc_html( $b['url'] ) . '</code></td></tr>';
		}
		echo '</tbody></table></div>';
	}
}
