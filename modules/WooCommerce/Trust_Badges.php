<?php
/**
 * WooCommerce Trust Badges module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Shows a reassurance line and accepted-payment labels under the Add to Cart
 * button to lift conversions.
 */
final class Trust_Badges extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_trust_badges';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Trust badges', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add a guarantee line and accepted-payment labels under the Add to Cart button.', 'dicestack' );
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
		return 'shield';
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
			'php_memory_kb' => 22,
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
				'key'     => 'message',
				'label'   => __( 'Reassurance line', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'Secure checkout — 30-day money-back guarantee', 'dicestack' ),
			),
			array(
				'key'     => 'methods',
				'label'   => __( 'Accepted payment labels', 'dicestack' ),
				'type'    => 'text',
				'default' => 'Visa · Mastercard · PayPal · Amex',
				'help'    => __( 'Shown as plain text under the reassurance line.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'render' ) );
	}

	/**
	 * Output the trust block.
	 *
	 * @return void
	 */
	public function render() {
		$message = (string) $this->get_setting( 'message', '' );
		$methods = (string) $this->get_setting( 'methods', '' );

		echo '<div class="dicestack-trust" style="margin-top:14px;font-size:13px;color:#6b7280;">';
		if ( '' !== trim( $message ) ) {
			echo '<div class="dicestack-trust-msg">&#128274; ' . esc_html( $message ) . '</div>';
		}
		if ( '' !== trim( $methods ) ) {
			echo '<div class="dicestack-trust-methods" style="margin-top:4px;font-weight:500;">' . esc_html( $methods ) . '</div>';
		}
		echo '</div>';
	}
}
