<?php
/**
 * Reviews Showcase module (no API key required).
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Displays customer/Google reviews with NO API key. Two modes:
 *  - Manual: add reviews on an admin page; rendered in grid/list/badge with
 *    Review + AggregateRating schema. 100% free, nothing external.
 *  - Embed: paste any third-party review widget embed code (Trustindex, Elfsight,
 *    a Google Maps embed, etc.) and it is output as-is.
 * Use [dicestack_reviews] anywhere.
 */
final class Google_Reviews extends Abstract_Module {

	/**
	 * Option storing manual reviews.
	 */
	const OPTION = 'dicestack_reviews';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'google_reviews';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Reviews showcase', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Show Google/customer reviews with [dicestack_reviews] — no API key needed.', 'dicestack' );
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
		return 'star';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 30,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0.5,
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
				'key'     => 'mode',
				'label'   => __( 'How to show reviews', 'dicestack' ),
				'type'    => 'select',
				'default' => 'manual',
				'options' => array(
					'manual' => __( 'Manual reviews (no API, add them yourself)', 'dicestack' ),
					'embed'  => __( 'Paste an embed code (Trustindex, Elfsight, Maps…)', 'dicestack' ),
				),
			),
			array(
				'key'     => 'design',
				'label'   => __( 'Manual layout', 'dicestack' ),
				'type'    => 'select',
				'default' => 'grid',
				'options' => array(
					'grid'  => __( 'Cards grid', 'dicestack' ),
					'list'  => __( 'Simple list', 'dicestack' ),
					'badge' => __( 'Compact rating badge', 'dicestack' ),
				),
			),
			array(
				'key'     => 'embed_code',
				'label'   => __( 'Embed code (for embed mode)', 'dicestack' ),
				'type'    => 'textarea',
				'default' => '',
				'help'    => __( 'Paste the widget code from any free reviews service. Only admins can save this.', 'dicestack' ),
			),
		);
	}

	/**
	 * Allow raw embed code only for capable users.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function save_settings( array $input ) {
		$schema_clean = parent::save_settings( $input );
		$raw          = isset( $input['embed_code'] ) ? (string) $input['embed_code'] : '';

		// Only users who can post unfiltered HTML may store raw embed markup
		// (on multisite, manage_options does NOT grant unfiltered_html). Everyone
		// else gets an iframe-only allowlist so no <script> can be stored.
		if ( current_user_can( 'unfiltered_html' ) ) {
			$schema_clean['embed_code'] = $raw;
		} else {
			$schema_clean['embed_code'] = wp_kses(
				$raw,
				array(
					'iframe' => array(
						'src'             => true,
						'width'           => true,
						'height'          => true,
						'style'           => true,
						'title'           => true,
						'loading'         => true,
						'frameborder'     => true,
						'allow'           => true,
						'allowfullscreen' => true,
					),
					'div'    => array( 'class' => true, 'id' => true, 'style' => true, 'data-*' => true ),
					'a'      => array( 'href' => true, 'class' => true, 'rel' => true, 'target' => true ),
					'span'   => array( 'class' => true, 'style' => true ),
				)
			);
		}
		update_option( $this->settings_option_key(), $schema_clean );
		return $schema_clean;
	}

	/**
	 * Read manual reviews.
	 *
	 * @return array[]
	 */
	private function reviews() {
		$data = get_option( self::OPTION, array() );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_shortcode( 'dicestack_reviews', array( $this, 'render' ) );
		add_shortcode( 'dicestack_google_reviews', array( $this, 'render' ) ); // back-compat alias.

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_page' ) );
			add_action( 'admin_post_dicestack_add_review', array( $this, 'handle_add' ) );
			add_action( 'admin_post_dicestack_delete_review', array( $this, 'handle_delete' ) );
		}
	}

	/**
	 * Register the manage-reviews page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'dicestack',
			__( 'Reviews', 'dicestack' ),
			__( 'Reviews', 'dicestack' ),
			'manage_options',
			'dicestack-reviews',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Add a manual review.
	 *
	 * @return void
	 */
	public function handle_add() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dicestack_review' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}
		$reviews   = $this->reviews();
		$reviews[] = array(
			'name'   => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'rating' => isset( $_POST['rating'] ) ? max( 1, min( 5, absint( $_POST['rating'] ) ) ) : 5,
			'date'   => isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '',
			'text'   => isset( $_POST['text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['text'] ) ) : '',
		);
		update_option( self::OPTION, $reviews );
		wp_safe_redirect( admin_url( 'admin.php?page=dicestack-reviews' ) );
		exit;
	}

	/**
	 * Delete a manual review by index.
	 *
	 * @return void
	 */
	public function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dicestack_review' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}
		$index   = isset( $_GET['i'] ) ? absint( $_GET['i'] ) : -1;
		$reviews = $this->reviews();
		if ( isset( $reviews[ $index ] ) ) {
			unset( $reviews[ $index ] );
			update_option( self::OPTION, array_values( $reviews ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=dicestack-reviews' ) );
		exit;
	}

	/**
	 * Render the manage-reviews page.
	 *
	 * @return void
	 */
	public function render_page() {
		$reviews = $this->reviews();
		echo '<div class="wrap"><h1>' . esc_html__( 'Reviews', 'dicestack' ) . '</h1>';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.
		if ( isset( $_GET['settings-saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'dicestack' ) . '</p></div>';
		}
		echo '<p>' . esc_html__( 'Add reviews here, then place [dicestack_reviews] on any page. No API key required.', 'dicestack' ) . '</p>';
		echo \DiceStack\Admin\Settings_Renderer::page_form( $this ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped internally.

		if ( $reviews ) {
			echo '<table class="widefat striped" style="margin-bottom:20px;"><thead><tr><th>' . esc_html__( 'Name', 'dicestack' ) . '</th><th>' . esc_html__( 'Rating', 'dicestack' ) . '</th><th>' . esc_html__( 'Review', 'dicestack' ) . '</th><th></th></tr></thead><tbody>';
			foreach ( $reviews as $i => $r ) {
				$del = wp_nonce_url( admin_url( 'admin-post.php?action=dicestack_delete_review&i=' . $i ), 'dicestack_review' );
				echo '<tr><td>' . esc_html( $r['name'] ) . '</td><td>' . esc_html( str_repeat( '★', (int) $r['rating'] ) ) . '</td><td>' . esc_html( wp_trim_words( $r['text'], 16 ) ) . '</td><td><a href="' . esc_url( $del ) . '" class="button-link-delete">' . esc_html__( 'Delete', 'dicestack' ) . '</a></td></tr>';
			}
			echo '</tbody></table>';
		}

		echo '<h2>' . esc_html__( 'Add a review', 'dicestack' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'dicestack_review' );
		echo '<input type="hidden" name="action" value="dicestack_add_review" />';
		echo '<table class="form-table">';
		echo '<tr><th>' . esc_html__( 'Name', 'dicestack' ) . '</th><td><input type="text" name="name" class="regular-text" required /></td></tr>';
		echo '<tr><th>' . esc_html__( 'Rating', 'dicestack' ) . '</th><td><select name="rating"><option>5</option><option>4</option><option>3</option><option>2</option><option>1</option></select></td></tr>';
		echo '<tr><th>' . esc_html__( 'Date (optional)', 'dicestack' ) . '</th><td><input type="text" name="date" class="regular-text" placeholder="e.g. June 2026" /></td></tr>';
		echo '<tr><th>' . esc_html__( 'Review text', 'dicestack' ) . '</th><td><textarea name="text" rows="4" class="large-text" required></textarea></td></tr>';
		echo '</table>';
		echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Add review', 'dicestack' ) . '</button></p>';
		echo '</form></div>';
	}

	/**
	 * Star markup.
	 *
	 * @param int $rating Rating.
	 * @return string
	 */
	private function stars( $rating ) {
		$rating = max( 0, min( 5, (int) $rating ) );
		return '<span style="color:#f5b400;">' . str_repeat( '★', $rating ) . '<span style="color:#ddd;">' . str_repeat( '★', 5 - $rating ) . '</span></span>';
	}

	/**
	 * Render reviews on the front end.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		if ( 'embed' === $this->get_setting( 'mode', 'manual' ) ) {
			// Admin-authored embed code, output verbatim.
			return (string) $this->get_setting( 'embed_code', '' );
		}

		$reviews = $this->reviews();
		if ( empty( $reviews ) ) {
			return '';
		}

		$design = (string) $this->get_setting( 'design', 'grid' );
		$count  = count( $reviews );
		$avg    = 0;
		foreach ( $reviews as $r ) {
			$avg += (int) $r['rating'];
		}
		$avg = $count ? round( $avg / $count, 1 ) : 0;

		// Aggregate schema.
		$schema = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'Organization',
			'name'            => get_bloginfo( 'name' ),
			'aggregateRating' => array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $avg,
				'reviewCount' => $count,
			),
		);
		$ld = '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>';

		if ( 'badge' === $design ) {
			return '<div class="dicestack-reviews-badge" style="display:inline-flex;align-items:center;gap:8px;border:1px solid #e4e7ec;border-radius:8px;padding:10px 14px;">'
				. '<strong style="font-size:20px;">' . esc_html( number_format_i18n( $avg, 1 ) ) . '</strong>'
				. $this->stars( (int) round( $avg ) )
				. '<span style="color:#6b7280;">' . esc_html( sprintf( /* translators: %d: count. */ _n( '%d review', '%d reviews', $count, 'dicestack' ), $count ) ) . '</span></div>' . $ld;
		}

		$wrap = 'grid' === $design ? 'display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;' : '';
		$html = '<div class="dicestack-reviews dicestack-reviews-' . esc_attr( $design ) . '" style="' . esc_attr( $wrap ) . '">';
		foreach ( $reviews as $r ) {
			$html .= '<div class="dicestack-review" style="border:1px solid #e4e7ec;border-radius:10px;padding:14px;">';
			$html .= '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;"><strong>' . esc_html( $r['name'] ) . '</strong>' . $this->stars( (int) $r['rating'] ) . '</div>';
			if ( ! empty( $r['date'] ) ) {
				$html .= '<div style="font-size:12px;color:#9aa3af;margin-bottom:6px;">' . esc_html( $r['date'] ) . '</div>';
			}
			$html .= '<p style="margin:0;color:#374151;">' . esc_html( $r['text'] ) . '</p></div>';
		}
		$html .= '</div>' . $ld;
		return $html;
	}
}
