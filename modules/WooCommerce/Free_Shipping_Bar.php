<?php
/**
 * WooCommerce Free Shipping Bar module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Shows "Spend X more for free shipping" on the cart and checkout to nudge
 * larger orders. Server-rendered, no extra assets.
 */
final class Free_Shipping_Bar extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_free_shipping_bar';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Free shipping nudge', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Encourage bigger carts with a "spend X more for free shipping" message.', 'dicestack' );
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
				'key'     => 'threshold',
				'label'   => __( 'Free shipping threshold', 'dicestack' ),
				'type'    => 'number',
				'default' => 50,
				'min'     => 1,
				'step'    => 1,
			),
			array(
				'key'     => 'reached_message',
				'label'   => __( 'Message when threshold is reached', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'You have unlocked free shipping!', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'woocommerce_before_cart', array( $this, 'render' ) );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'render' ) );
	}

	/**
	 * Render the nudge.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}
		$threshold = (float) $this->get_setting( 'threshold', 50 );
		if ( $threshold <= 0 ) {
			return;
		}
		$subtotal = (float) WC()->cart->get_subtotal();

		if ( $subtotal >= $threshold ) {
			$message = (string) $this->get_setting( 'reached_message', __( 'You have unlocked free shipping!', 'dicestack' ) );
		} else {
			$remaining = $threshold - $subtotal;
			$message   = sprintf(
				/* translators: %s: remaining amount. */
				esc_html__( 'Spend %s more to get free shipping!', 'dicestack' ),
				wp_strip_all_tags( wc_price( $remaining ) )
			);
		}

		echo '<div class="dicestack-free-ship" style="background:#e1f5ee;color:#0f6e56;padding:10px 14px;border-radius:8px;margin-bottom:16px;text-align:center;">' . esc_html( $message ) . '</div>';
	}
}
