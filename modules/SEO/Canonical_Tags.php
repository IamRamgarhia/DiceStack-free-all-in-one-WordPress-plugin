<?php
/**
 * Canonical Tags module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds self-referencing canonical tags to archive, home, and search pages
 * (WordPress only adds them to singular content by default), reducing duplicate
 * content issues.
 */
final class Canonical_Tags extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'canonical_tags';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Archive canonical tags', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add self-referencing canonical URLs to archives and the home page.', 'dicestack' );
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
		add_action( 'wp_head', array( $this, 'output' ), 1 );
	}

	/**
	 * Print a canonical tag for non-singular views (core handles singular).
	 *
	 * @return void
	 */
	public function output() {
		if ( is_singular() ) {
			return; // WordPress core already outputs this.
		}

		$url = '';
		if ( is_front_page() ) {
			$url = home_url( '/' );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term && ! empty( $term->term_id ) ) {
				$link = get_term_link( $term );
				$url  = is_wp_error( $link ) ? '' : $link;
			}
		} elseif ( is_post_type_archive() ) {
			$url = get_post_type_archive_link( get_post_type() );
		}

		if ( $url ) {
			echo '<link rel="canonical" href="' . esc_url( $url ) . '" />' . "\n";
		}
	}
}
