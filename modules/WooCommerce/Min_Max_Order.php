<?php
/**
 * WooCommerce Min/Max Order module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Enforces a minimum and/or maximum order total before checkout. Replaces
 * WooCommerce Min/Max Quantities for the order-total case.
 */
final class Min_Max_Order extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_min_max_order';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Min / max order limits', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Require a minimum (or cap a maximum) order total before checkout.', 'dicestack' );
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
	public function replaces() {
		return 'premium order-limit plugins';
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
				'key'     => 'min_total',
				'label'   => __( 'Minimum order total', 'dicestack' ),
				'type'    => 'number',
				'default' => 0,
				'min'     => 0,
				'step'    => 1,
				'help'    => __( '0 = no minimum.', 'dicestack' ),
			),
			array(
				'key'     => 'max_total',
				'label'   => __( 'Maximum order total', 'dicestack' ),
				'type'    => 'number',
				'default' => 0,
				'min'     => 0,
				'step'    => 1,
				'help'    => __( '0 = no maximum.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'woocommerce_check_cart_items', array( $this, 'validate' ) );
	}

	/**
	 * Validate the cart total against the configured limits.
	 *
	 * @return void
	 */
	public function validate() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}
		$total = (float) WC()->cart->get_subtotal();
		$min   = (float) $this->get_setting( 'min_total', 0 );
		$max   = (float) $this->get_setting( 'max_total', 0 );

		if ( $min > 0 && $total < $min ) {
			wc_add_notice(
				sprintf(
					/* translators: 1: minimum amount, 2: current amount. */
					esc_html__( 'A minimum order of %1$s is required. Your current total is %2$s.', 'dicestack' ),
					wp_kses_post( wc_price( $min ) ),
					wp_kses_post( wc_price( $total ) )
				),
				'error'
			);
		}

		if ( $max > 0 && $total > $max ) {
			wc_add_notice(
				sprintf(
					/* translators: 1: maximum amount, 2: current amount. */
					esc_html__( 'The maximum order total is %1$s. Your current total is %2$s.', 'dicestack' ),
					wp_kses_post( wc_price( $max ) ),
					wp_kses_post( wc_price( $total ) )
				),
				'error'
			);
		}
	}
}
