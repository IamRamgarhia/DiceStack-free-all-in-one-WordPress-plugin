<?php
/**
 * WooCommerce Low Stock Display module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Shows an urgency message like "Only 3 left in stock" on product pages when
 * inventory is low.
 */
final class Low_Stock_Display extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_low_stock_display';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Low-stock urgency', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Show "Only X left" on product pages when stock runs low to drive urgency.', 'dicestack' );
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
			'php_memory_kb' => 16,
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
				'label'   => __( 'Show when stock is at or below', 'dicestack' ),
				'type'    => 'number',
				'default' => 5,
				'min'     => 1,
				'max'     => 100,
				'step'    => 1,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'woocommerce_single_product_summary', array( $this, 'render' ), 25 );
	}

	/**
	 * Output the low-stock message.
	 *
	 * @return void
	 */
	public function render() {
		global $product;
		if ( ! $product instanceof \WC_Product || ! $product->managing_stock() ) {
			return;
		}
		$qty = $product->get_stock_quantity();
		$max = (int) $this->get_setting( 'threshold', 5 );
		if ( null === $qty || $qty <= 0 || $qty > $max ) {
			return;
		}
		$msg = sprintf(
			/* translators: %d: units left. */
			_n( 'Only %d left in stock — order soon!', 'Only %d left in stock — order soon!', $qty, 'dicestack' ),
			$qty
		);
		echo '<p class="dicestack-low-stock" style="color:#a32d2d;font-weight:500;">' . esc_html( $msg ) . '</p>';
	}
}
