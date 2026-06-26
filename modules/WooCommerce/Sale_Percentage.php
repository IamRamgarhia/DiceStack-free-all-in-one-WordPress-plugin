<?php
/**
 * WooCommerce Sale Percentage module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Replaces WooCommerce's plain "Sale!" flash with the actual discount
 * percentage (e.g. "-25%"), which converts better.
 */
final class Sale_Percentage extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_sale_percentage';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Sale percentage flash', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Show the exact discount (e.g. -25%) on the sale badge instead of just "Sale".', 'dicestack' );
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
		return 'tags';
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
	public function init() {
		add_filter( 'woocommerce_sale_flash', array( $this, 'flash' ), 10, 3 );
	}

	/**
	 * Replace the sale flash text with a percentage.
	 *
	 * @param string      $html    Default flash HTML.
	 * @param \WP_Post    $post    Post object.
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	public function flash( $html, $post, $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return $html;
		}

		$percent = 0;
		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $child_id ) {
				$child = wc_get_product( $child_id );
				if ( $child && $child->is_on_sale() ) {
					$percent = max( $percent, $this->calc( $child ) );
				}
			}
		} else {
			$percent = $this->calc( $product );
		}

		if ( $percent <= 0 ) {
			return $html;
		}
		return '<span class="onsale">-' . esc_html( $percent ) . '%</span>';
	}

	/**
	 * Calculate the discount percentage for a product.
	 *
	 * @param \WC_Product $product Product.
	 * @return int
	 */
	private function calc( $product ) {
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();
		if ( $regular <= 0 || $sale <= 0 || $sale >= $regular ) {
			return 0;
		}
		return (int) round( ( ( $regular - $sale ) / $regular ) * 100 );
	}
}
