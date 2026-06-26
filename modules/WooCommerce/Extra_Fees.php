<?php
/**
 * WooCommerce Extra Fees module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a flat and/or percentage handling fee to the cart. Replaces the
 * WooCommerce Extra Fees plugin.
 */
final class Extra_Fees extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_extra_fees';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Extra checkout fees', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add a handling or processing fee to every order — flat amount, percentage, or both.', 'dicestack' );
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
		return 'premium checkout-fee plugins';
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
			'php_memory_kb' => 25,
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
				'key'     => 'label',
				'label'   => __( 'Fee label', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'Handling fee', 'dicestack' ),
			),
			array(
				'key'     => 'flat',
				'label'   => __( 'Flat fee amount', 'dicestack' ),
				'type'    => 'number',
				'default' => 0,
				'min'     => 0,
				'step'    => 0.01,
			),
			array(
				'key'     => 'percent',
				'label'   => __( 'Percentage fee (% of subtotal)', 'dicestack' ),
				'type'    => 'number',
				'default' => 0,
				'min'     => 0,
				'max'     => 100,
				'step'    => 0.1,
			),
			array(
				'key'     => 'taxable',
				'label'   => __( 'Fee is taxable', 'dicestack' ),
				'type'    => 'toggle',
				'default' => false,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_fee' ) );
	}

	/**
	 * Add the configured fee to the cart.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return void
	 */
	public function add_fee( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		$flat    = (float) $this->get_setting( 'flat', 0 );
		$percent = (float) $this->get_setting( 'percent', 0 );
		if ( $flat <= 0 && $percent <= 0 ) {
			return;
		}

		$subtotal = (float) $cart->get_subtotal();
		$amount   = $flat + ( $subtotal * ( $percent / 100 ) );
		if ( $amount <= 0 ) {
			return;
		}

		$label   = (string) $this->get_setting( 'label', __( 'Handling fee', 'dicestack' ) );
		$taxable = ! empty( $this->get_setting( 'taxable', false ) );
		$cart->add_fee( $label, round( $amount, 2 ), $taxable );
	}
}
