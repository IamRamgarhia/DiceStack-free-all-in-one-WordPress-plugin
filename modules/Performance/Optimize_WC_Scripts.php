<?php
/**
 * Optimize WooCommerce Scripts module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Stops WooCommerce loading its scripts, styles, and cart fragments on pages
 * that aren't part of the store (e.g. blog posts, the home page). A big win on
 * content-heavy WooCommerce sites.
 */
final class Optimize_WC_Scripts extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'optimize_wc_scripts';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Optimize WooCommerce scripts', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Only load WooCommerce assets on store pages, not on every page of the site.', 'dicestack' );
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
	public function replaces() {
		return 'premium performance plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function dependencies() {
		return array( 'woocommerce' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 20,
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
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue' ), 99 );
	}

	/**
	 * Dequeue WooCommerce assets away from store pages.
	 *
	 * @return void
	 */
	public function dequeue() {
		if ( ! function_exists( 'is_woocommerce' ) ) {
			return;
		}
		$is_store = is_woocommerce() || is_cart() || is_checkout() || is_account_page();
		// Also keep them where a [products]/cart shortcode might be used.
		if ( $is_store ) {
			return;
		}
		global $post;
		if ( $post instanceof \WP_Post && has_shortcode( (string) $post->post_content, 'products' ) ) {
			return;
		}

		wp_dequeue_style( 'woocommerce-general' );
		wp_dequeue_style( 'woocommerce-layout' );
		wp_dequeue_style( 'woocommerce-smallscreen' );
		wp_dequeue_style( 'wc-blocks-style' );
		wp_dequeue_script( 'woocommerce' );
		wp_dequeue_script( 'wc-cart-fragments' );
		wp_dequeue_script( 'wc-add-to-cart' );
	}
}
