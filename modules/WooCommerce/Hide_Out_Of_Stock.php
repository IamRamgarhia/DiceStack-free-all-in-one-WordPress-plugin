<?php
/**
 * WooCommerce Hide Out-of-Stock module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Hides out-of-stock products from shop and category listings so customers only
 * see what they can buy.
 */
final class Hide_Out_Of_Stock extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_hide_out_of_stock';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Hide out-of-stock products', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Remove out-of-stock items from shop and category pages.', 'dicestack' );
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
			'php_memory_kb' => 18,
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
		add_filter( 'woocommerce_product_query_meta_query', array( $this, 'exclude' ) );
	}

	/**
	 * Add a meta query that excludes out-of-stock products.
	 *
	 * @param array $meta_query Existing meta query.
	 * @return array
	 */
	public function exclude( $meta_query ) {
		$meta_query[] = array(
			'key'     => '_stock_status',
			'value'   => 'outofstock',
			'compare' => '!=',
		);
		return $meta_query;
	}
}
