<?php
/**
 * RSS Protect module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Appends a "source" credit link to each RSS item so that scraper sites that
 * republish your feed link back to the original — a small SEO defence.
 */
final class RSS_Protect extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'rss_protect';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'RSS source credit', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add a link back to the original post in your RSS feed to fight content scrapers.', 'dicestack' );
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
			'php_memory_kb' => 12,
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
		add_filter( 'the_excerpt_rss', array( $this, 'append' ) );
		add_filter( 'the_content_feed', array( $this, 'append' ) );
	}

	/**
	 * Append the source credit.
	 *
	 * @param string $content Feed content.
	 * @return string
	 */
	public function append( $content ) {
		$credit = sprintf(
			/* translators: 1: post link, 2: site name. */
			__( 'The post appeared first on %2$s: %1$s', 'dicestack' ),
			esc_url( get_permalink() ),
			esc_html( get_bloginfo( 'name' ) )
		);
		return $content . '<p>' . $credit . '</p>';
	}
}
