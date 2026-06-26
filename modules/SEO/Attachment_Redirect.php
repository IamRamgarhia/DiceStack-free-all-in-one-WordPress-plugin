<?php
/**
 * Attachment Page Redirect module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Redirects thin attachment pages to their parent post (or home). Stops empty
 * "media" pages from being indexed and competing in search.
 */
final class Attachment_Redirect extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'attachment_redirect';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Attachment page redirect', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Redirect thin media attachment pages to their parent post for cleaner SEO.', 'dicestack' );
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
			'php_memory_kb' => 15,
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
		add_action( 'template_redirect', array( $this, 'redirect' ) );
	}

	/**
	 * Redirect attachment pages.
	 *
	 * @return void
	 */
	public function redirect() {
		if ( ! is_attachment() ) {
			return;
		}
		$post = get_queried_object();
		if ( $post && ! empty( $post->post_parent ) ) {
			wp_safe_redirect( get_permalink( $post->post_parent ), 301 );
			exit;
		}
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
