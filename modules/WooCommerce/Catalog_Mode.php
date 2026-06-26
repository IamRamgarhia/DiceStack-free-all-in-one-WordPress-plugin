<?php
/**
 * WooCommerce Catalog Mode module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Turns the store into a catalog: hides Add to Cart buttons and, optionally,
 * prices. Useful for wholesale, "enquire only", or coming-soon stores.
 */
final class Catalog_Mode extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_catalog_mode';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Catalog mode', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Hide Add to Cart buttons (and optionally prices) to run an enquiry-only catalog.', 'dicestack' );
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
		return 'shopping-cart';
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
			'php_memory_kb' => 30,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 0,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function settings_schema() {
		return array(
			array(
				'key'     => 'hide_prices',
				'label'   => __( 'Also hide prices', 'dicestack' ),
				'type'    => 'toggle',
				'default' => false,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		// Make nothing purchasable.
		add_filter( 'woocommerce_is_purchasable', '__return_false' );

		// Remove the add-to-cart buttons in loop and single.
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
		add_action( 'init', array( $this, 'remove_single_add_to_cart' ) );

		if ( ! empty( $this->get_setting( 'hide_prices', false ) ) ) {
			add_filter( 'woocommerce_get_price_html', '__return_empty_string' );
		}
	}

	/**
	 * Remove the single-product add-to-cart area.
	 *
	 * @return void
	 */
	public function remove_single_add_to_cart() {
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	}
}
