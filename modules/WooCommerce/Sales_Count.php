<?php
/**
 * WooCommerce Sales Count module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Displays "X sold" social proof on product pages using WooCommerce's built-in
 * total_sales figure. No external service.
 */
final class Sales_Count extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_sales_count';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Units-sold counter', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Show "X sold" on product pages to build buyer confidence.', 'dicestack' );
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
				'key'     => 'min_sales',
				'label'   => __( 'Only show once a product has sold at least', 'dicestack' ),
				'type'    => 'number',
				'default' => 5,
				'min'     => 1,
				'step'    => 1,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'woocommerce_single_product_summary', array( $this, 'render' ), 11 );
	}

	/**
	 * Output the sold count.
	 *
	 * @return void
	 */
	public function render() {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$sold = (int) $product->get_total_sales();
		$min  = (int) $this->get_setting( 'min_sales', 5 );
		if ( $sold < $min ) {
			return;
		}
		$label = sprintf(
			/* translators: %s: number of units sold. */
			_n( '%s sold', '%s sold', $sold, 'dicestack' ),
			number_format_i18n( $sold )
		);
		echo '<p class="dicestack-sold" style="color:#3b6d11;font-weight:500;">' . esc_html( $label ) . '</p>';
	}
}
