<?php
/**
 * WooCommerce Disable Reviews module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Turns off WooCommerce product reviews and the reviews tab for stores that
 * don't want them.
 */
final class Disable_Reviews extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_disable_reviews';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Disable product reviews', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Remove product reviews and the reviews tab from your store.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'woocommerce';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'message-off';
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
		add_filter( 'woocommerce_product_tabs', array( $this, 'remove_tab' ), 98 );
		add_filter( 'comments_open', array( $this, 'close_for_products' ), 10, 2 );
	}

	/**
	 * Remove the reviews tab.
	 *
	 * @param array $tabs Product tabs.
	 * @return array
	 */
	public function remove_tab( $tabs ) {
		unset( $tabs['reviews'] );
		return $tabs;
	}

	/**
	 * Close comments (reviews) on products.
	 *
	 * @param bool $open    Whether open.
	 * @param int  $post_id Post ID.
	 * @return bool
	 */
	public function close_for_products( $open, $post_id ) {
		if ( 'product' === get_post_type( $post_id ) ) {
			return false;
		}
		return $open;
	}
}
