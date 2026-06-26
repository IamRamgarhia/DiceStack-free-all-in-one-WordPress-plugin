<?php
/**
 * Clean Head module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Removes rarely-needed tags WordPress injects into <head> (shortlink, RSD,
 * Windows Live Writer manifest, adjacent-post links) for a leaner head.
 */
final class Clean_Head extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'clean_head';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Clean up <head>', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Remove shortlink, RSD, WLW manifest, and adjacent-post link tags from the head.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'performance';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'bolt';
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
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
	}
}
