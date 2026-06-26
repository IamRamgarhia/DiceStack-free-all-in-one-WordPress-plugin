<?php
/**
 * llms.txt Generator module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Serves a /llms.txt file describing the site for AI assistants and LLM crawlers
 * (the emerging convention for AI visibility). Generated on the fly from your
 * site info and most important pages.
 */
final class LLMs_Txt extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'llms_txt';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'llms.txt for AI', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Publish an llms.txt so AI assistants can understand and cite your site.', 'dicestack' );
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
		return 'world';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 20,
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
				'key'     => 'tagline',
				'label'   => __( 'One-line site summary', 'dicestack' ),
				'type'    => 'text',
				'default' => get_bloginfo( 'description' ),
			),
			array(
				'key'     => 'extra',
				'label'   => __( 'Extra notes for AI (optional)', 'dicestack' ),
				'type'    => 'textarea',
				'default' => '',
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'init', array( $this, 'maybe_serve' ) );
	}

	/**
	 * Serve /llms.txt when requested.
	 *
	 * @return void
	 */
	public function maybe_serve() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		if ( '/llms.txt' !== untrailingslashit( (string) $uri ) ) {
			return;
		}

		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );

		$name    = get_bloginfo( 'name' );
		$tagline = (string) $this->get_setting( 'tagline', get_bloginfo( 'description' ) );

		echo '# ' . esc_html( $name ) . "\n\n";
		if ( '' !== trim( $tagline ) ) {
			echo '> ' . esc_html( $tagline ) . "\n\n";
		}
		echo esc_html__( 'Site URL', 'dicestack' ) . ': ' . esc_url_raw( home_url( '/' ) ) . "\n\n";

		echo '## ' . esc_html__( 'Key pages', 'dicestack' ) . "\n\n";
		$pages = get_pages( array( 'sort_column' => 'menu_order', 'number' => 25 ) );
		foreach ( (array) $pages as $page ) {
			echo '- [' . esc_html( get_the_title( $page ) ) . '](' . esc_url_raw( get_permalink( $page ) ) . ")\n";
		}

		echo "\n## " . esc_html__( 'Recent posts', 'dicestack' ) . "\n\n";
		$posts = get_posts( array( 'numberposts' => 15 ) );
		foreach ( (array) $posts as $post ) {
			echo '- [' . esc_html( get_the_title( $post ) ) . '](' . esc_url_raw( get_permalink( $post ) ) . ")\n";
		}

		$extra = (string) $this->get_setting( 'extra', '' );
		if ( '' !== trim( $extra ) ) {
			echo "\n## " . esc_html__( 'Notes', 'dicestack' ) . "\n\n" . esc_html( $extra ) . "\n";
		}

		exit;
	}
}
