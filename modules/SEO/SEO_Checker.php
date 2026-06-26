<?php
/**
 * SEO & AI Visibility Checker module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Runs a free, on-site audit of common SEO issues and AI-visibility signals and
 * lists pass / warn / fail results with fixes — no external service needed.
 */
final class SEO_Checker extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'seo_checker';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'SEO & AI visibility checker', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Audit your site for SEO issues and AI-crawler visibility, with fixes.', 'dicestack' );
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
	public function performance_profile() {
		return array(
			'php_memory_kb' => 40,
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
	 * Register the audit page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'dicestack',
			__( 'SEO checker', 'dicestack' ),
			__( 'SEO checker', 'dicestack' ),
			'manage_options',
			'dicestack-seo-checker',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Build the list of checks with results.
	 *
	 * @return array[] Each: label, status (pass|warn|fail), fix.
	 */
	private function run_checks() {
		$core    = \DiceStack\Core::instance();
		$active  = $core->get_active_modules();
		$checks  = array();

		// HTTPS.
		$https = ( 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME ) );
		$checks[] = array(
			'label'  => __( 'Site uses HTTPS', 'dicestack' ),
			'status' => $https ? 'pass' : 'fail',
			'fix'    => __( 'Install an SSL certificate and enable the Force HTTPS module.', 'dicestack' ),
		);

		// Search engine visibility.
		$public = (bool) get_option( 'blog_public' );
		$checks[] = array(
			'label'  => __( 'Search engines are allowed to index the site', 'dicestack' ),
			'status' => $public ? 'pass' : 'fail',
			'fix'    => __( 'Settings → Reading → uncheck "Discourage search engines".', 'dicestack' ),
		);

		// Meta tags module.
		$checks[] = array(
			'label'  => __( 'Meta description / Open Graph output', 'dicestack' ),
			'status' => in_array( 'meta_tags', $active, true ) ? 'pass' : 'warn',
			'fix'    => __( 'Enable the SEO meta tags module.', 'dicestack' ),
		);

		// Schema.
		$checks[] = array(
			'label'  => __( 'Structured data (schema) output', 'dicestack' ),
			'status' => in_array( 'schema_jsonld', $active, true ) ? 'pass' : 'warn',
			'fix'    => __( 'Enable the Schema / structured data module.', 'dicestack' ),
		);

		// Sitemap (core or our module).
		$has_sitemap = in_array( 'robots_txt', $active, true ) || function_exists( 'wp_sitemaps_get_server' );
		$checks[] = array(
			'label'  => __( 'XML sitemap available', 'dicestack' ),
			'status' => $has_sitemap ? 'pass' : 'warn',
			'fix'    => __( 'WordPress core provides /wp-sitemap.xml; ensure it is not disabled.', 'dicestack' ),
		);

		// AI visibility: llms.txt.
		$checks[] = array(
			'label'  => __( 'llms.txt present for AI assistants', 'dicestack' ),
			'status' => in_array( 'llms_txt', $active, true ) ? 'pass' : 'warn',
			'fix'    => __( 'Enable the llms.txt for AI module.', 'dicestack' ),
		);

		// Site title / tagline.
		$checks[] = array(
			'label'  => __( 'Site title and tagline set', 'dicestack' ),
			'status' => ( get_bloginfo( 'name' ) && get_bloginfo( 'description' ) ) ? 'pass' : 'warn',
			'fix'    => __( 'Settings → General → set a clear title and tagline.', 'dicestack' ),
		);

		// Permalinks.
		$checks[] = array(
			'label'  => __( 'SEO-friendly (pretty) permalinks', 'dicestack' ),
			'status' => ( '' !== get_option( 'permalink_structure' ) ) ? 'pass' : 'fail',
			'fix'    => __( 'Settings → Permalinks → choose Post name.', 'dicestack' ),
		);

		// Images missing alt (sample).
		$missing = $this->count_images_without_alt();
		$checks[] = array(
			'label'  => sprintf( /* translators: %d: count. */ __( 'Images with alt text (%d missing in recent media)', 'dicestack' ), $missing ),
			'status' => ( 0 === $missing ) ? 'pass' : 'warn',
			'fix'    => __( 'Enable Auto image alt text, or add alt text in the Media Library.', 'dicestack' ),
		);

		return $checks;
	}

	/**
	 * Count recent image attachments without alt text.
	 *
	 * @return int
	 */
	private function count_images_without_alt() {
		$images = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'numberposts'    => 50,
				'fields'         => 'ids',
				'post_status'    => 'inherit',
			)
		);
		$missing = 0;
		foreach ( (array) $images as $id ) {
			if ( '' === trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ) {
				$missing++;
			}
		}
		return $missing;
	}

	/**
	 * Render the audit page.
	 *
	 * @return void
	 */
	public function render_page() {
		$checks = $this->run_checks();
		$colors = array(
			'pass' => '#3b6d11',
			'warn' => '#854f0b',
			'fail' => '#a32d2d',
		);
		$labels = array(
			'pass' => __( 'Pass', 'dicestack' ),
			'warn' => __( 'Improve', 'dicestack' ),
			'fail' => __( 'Fix', 'dicestack' ),
		);

		echo '<div class="wrap"><h1>' . esc_html__( 'SEO & AI visibility checker', 'dicestack' ) . '</h1>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Check', 'dicestack' ) . '</th><th>' . esc_html__( 'Status', 'dicestack' ) . '</th><th>' . esc_html__( 'How to fix', 'dicestack' ) . '</th></tr></thead><tbody>';
		foreach ( $checks as $c ) {
			$color = isset( $colors[ $c['status'] ] ) ? $colors[ $c['status'] ] : '#555';
			echo '<tr>';
			echo '<td>' . esc_html( $c['label'] ) . '</td>';
			echo '<td><span style="color:#fff;background:' . esc_attr( $color ) . ';padding:2px 8px;border-radius:4px;font-size:12px;">' . esc_html( $labels[ $c['status'] ] ) . '</span></td>';
			echo '<td>' . ( 'pass' === $c['status'] ? '—' : esc_html( $c['fix'] ) ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}
}
