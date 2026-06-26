<?php
/**
 * WooCommerce Redirect After Add to Cart module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Sends customers straight to the cart (or checkout) after adding a product,
 * shortening the path to purchase.
 */
final class Redirect_After_Add extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_redirect_after_add';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Redirect after add to cart', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Take customers to the cart or checkout immediately after they add a product.', 'dicestack' );
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
	public function settings_schema() {
		return array(
			array(
				'key'     => 'destination',
				'label'   => __( 'Send customer to', 'dicestack' ),
				'type'    => 'select',
				'default' => 'cart',
				'options' => array(
					'cart'     => __( 'Cart', 'dicestack' ),
					'checkout' => __( 'Checkout', 'dicestack' ),
				),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'redirect' ) );
	}

	/**
	 * Provide the redirect URL.
	 *
	 * @param string $url Default redirect URL.
	 * @return string
	 */
	public function redirect( $url ) {
		if ( 'checkout' === $this->get_setting( 'destination', 'cart' ) && function_exists( 'wc_get_checkout_url' ) ) {
			return wc_get_checkout_url();
		}
		return function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : $url;
	}
}
