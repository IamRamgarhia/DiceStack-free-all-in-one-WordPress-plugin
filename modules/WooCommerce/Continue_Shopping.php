<?php
/**
 * WooCommerce Continue Shopping module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a "Continue shopping" button to the cart page linking back to the shop.
 */
final class Continue_Shopping extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_continue_shopping';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Continue shopping button', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add a "continue shopping" link to the cart so customers return to browsing.', 'dicestack' );
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
	public function init() {
		add_action( 'woocommerce_before_cart', array( $this, 'render' ) );
	}

	/**
	 * Render the button.
	 *
	 * @return void
	 */
	public function render() {
		$shop = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/' );
		if ( ! $shop ) {
			$shop = home_url( '/' );
		}
		echo '<a href="' . esc_url( $shop ) . '" class="button dicestack-continue" style="margin-bottom:16px;display:inline-block;">&larr; ' . esc_html__( 'Continue shopping', 'dicestack' ) . '</a>';
	}
}
