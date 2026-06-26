<?php
/**
 * Meta Tags (SEO) module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Outputs meta description, Open Graph, and Twitter Card tags, with a per-post
 * editor box for a custom title and description. Replaces the core of Yoast.
 */
final class Meta_Tags extends Abstract_Module {

	/**
	 * Meta key for the custom description.
	 */
	const META_DESC = '_dicestack_meta_description';

	/**
	 * Meta key for the custom SEO title.
	 */
	const META_TITLE = '_dicestack_meta_title';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'meta_tags';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'SEO meta tags', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add meta descriptions, Open Graph, and Twitter Card tags for better search and social sharing.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'seo';
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
		return 'premium SEO plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 110,
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
				'key'     => 'enable_og',
				'label'   => __( 'Output Open Graph tags (Facebook, LinkedIn)', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'enable_twitter',
				'label'   => __( 'Output Twitter Card tags', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'title_separator',
				'label'   => __( 'Title separator', 'dicestack' ),
				'type'    => 'select',
				'default' => 'dash',
				'options' => array(
					'dash'   => '–',
					'pipe'   => '|',
					'bullet' => '•',
				),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_head', array( $this, 'output_head_tags' ), 1 );
		add_filter( 'document_title_parts', array( $this, 'filter_title' ) );

		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
			add_action( 'save_post', array( $this, 'save_meta_box' ), 10, 2 );
		}
	}

	/**
	 * Replace the document title for singular posts with the custom SEO title.
	 *
	 * @param array $parts Title parts.
	 * @return array
	 */
	public function filter_title( $parts ) {
		if ( is_singular() ) {
			$custom = get_post_meta( get_queried_object_id(), self::META_TITLE, true );
			if ( $custom ) {
				$parts['title'] = $custom;
			}
		}
		return $parts;
	}

	/**
	 * Print head meta tags.
	 *
	 * @return void
	 */
	public function output_head_tags() {
		$s           = $this->get_settings();
		$description = $this->current_description();
		$title       = wp_get_document_title();
		$url         = $this->current_url();
		$image       = ( is_singular() && has_post_thumbnail() ) ? get_the_post_thumbnail_url( get_queried_object_id(), 'large' ) : '';

		if ( $description ) {
			echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
		}

		if ( ! empty( $s['enable_og'] ) ) {
			echo '<meta property="og:type" content="' . ( is_singular() ? 'article' : 'website' ) . '" />' . "\n";
			echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
			if ( $description ) {
				echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
			}
			echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
			echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";
			if ( $image ) {
				echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
			}
		}

		if ( ! empty( $s['enable_twitter'] ) ) {
			echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '" />' . "\n";
			echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
			if ( $description ) {
				echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '" />' . "\n";
			}
			if ( $image ) {
				echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
			}
		}
	}

	/**
	 * Resolve the best description for the current request.
	 *
	 * @return string
	 */
	private function current_description() {
		if ( is_singular() ) {
			$id     = get_queried_object_id();
			$custom = get_post_meta( $id, self::META_DESC, true );
			if ( $custom ) {
				return $custom;
			}
			$post = get_post( $id );
			if ( $post ) {
				$excerpt = $post->post_excerpt ? $post->post_excerpt : wp_strip_all_tags( $post->post_content );
				return wp_trim_words( $excerpt, 30, '' );
			}
		}
		return get_bloginfo( 'description' );
	}

	/**
	 * Current canonical URL.
	 *
	 * @return string
	 */
	private function current_url() {
		if ( is_singular() ) {
			return get_permalink( get_queried_object_id() );
		}
		return home_url( add_query_arg( array(), null ) );
	}

	/**
	 * Register the editor meta box.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		foreach ( get_post_types( array( 'public' => true ) ) as $type ) {
			add_meta_box(
				'dicestack_seo',
				__( 'DiceStack SEO', 'dicestack' ),
				array( $this, 'render_meta_box' ),
				$type,
				'normal',
				'default'
			);
		}
	}

	/**
	 * Render the editor meta box.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'dicestack_seo_save', 'dicestack_seo_nonce' );
		$title = get_post_meta( $post->ID, self::META_TITLE, true );
		$desc  = get_post_meta( $post->ID, self::META_DESC, true );
		?>
		<p>
			<label for="dicestack_seo_title"><strong><?php esc_html_e( 'SEO title', 'dicestack' ); ?></strong></label><br />
			<input type="text" id="dicestack_seo_title" name="dicestack_seo_title" value="<?php echo esc_attr( $title ); ?>" class="widefat" maxlength="70" />
		</p>
		<p>
			<label for="dicestack_seo_desc"><strong><?php esc_html_e( 'Meta description', 'dicestack' ); ?></strong></label><br />
			<textarea id="dicestack_seo_desc" name="dicestack_seo_desc" rows="3" class="widefat" maxlength="160"><?php echo esc_textarea( $desc ); ?></textarea>
			<span class="description"><?php esc_html_e( 'Recommended: up to 160 characters.', 'dicestack' ); ?></span>
		</p>
		<?php
	}

	/**
	 * Save the meta box.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function save_meta_box( $post_id, $post ) {
		if ( ! isset( $_POST['dicestack_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dicestack_seo_nonce'] ) ), 'dicestack_seo_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$title = isset( $_POST['dicestack_seo_title'] ) ? sanitize_text_field( wp_unslash( $_POST['dicestack_seo_title'] ) ) : '';
		$desc  = isset( $_POST['dicestack_seo_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['dicestack_seo_desc'] ) ) : '';

		update_post_meta( $post_id, self::META_TITLE, $title );
		update_post_meta( $post_id, self::META_DESC, $desc );
	}
}
