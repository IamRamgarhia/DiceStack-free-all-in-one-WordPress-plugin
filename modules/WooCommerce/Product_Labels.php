<?php
/**
 * WooCommerce Product Labels module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds Sale / New / Featured / Out-of-stock badges to product images in the
 * shop and on single products. Replaces YITH Badge Management.
 */
final class Product_Labels extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_product_labels';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Product labels & badges', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Show Sale, New, Featured, and Out-of-stock badges on product images.', 'dicestack' );
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
	public function replaces() {
		return 'premium product-badge plugins';
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
			'php_memory_kb' => 45,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0.5,
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
				'key'     => 'new_days',
				'label'   => __( 'Mark products "New" for (days after publish)', 'dicestack' ),
				'type'    => 'number',
				'default' => 30,
				'min'     => 0,
				'max'     => 365,
				'step'    => 1,
				'help'    => __( 'Set to 0 to disable the New badge.', 'dicestack' ),
			),
			array(
				'key'     => 'show_featured',
				'label'   => __( 'Show a "Featured" badge', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'show_outofstock',
				'label'   => __( 'Show an "Out of stock" badge', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'render_badges' ), 9 );
		add_action( 'woocommerce_before_single_product_summary', array( $this, 'render_badges' ), 9 );
		add_action( 'wp_head', array( $this, 'styles' ) );
	}

	/**
	 * Minimal badge styles.
	 *
	 * @return void
	 */
	public function styles() {
		echo '<style>.dicestack-badges{position:absolute;top:8px;left:8px;z-index:5;display:flex;flex-direction:column;gap:4px}.dicestack-badge-wc{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;color:#fff;line-height:1.6}.dicestack-b-sale{background:#d4537e}.dicestack-b-new{background:#0aa2c0}.dicestack-b-feat{background:#854f0b}.dicestack-b-oos{background:#6b7280}</style>';
	}

	/**
	 * Render badges for the current product.
	 *
	 * @return void
	 */
	public function render_badges() {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$badges = array();

		if ( $product->is_on_sale() ) {
			$badges[] = array( 'sale', __( 'Sale', 'dicestack' ) );
		}

		$new_days = (int) $this->get_setting( 'new_days', 30 );
		if ( $new_days > 0 ) {
			$created = $product->get_date_created();
			if ( $created && ( time() - $created->getTimestamp() ) < ( $new_days * DAY_IN_SECONDS ) ) {
				$badges[] = array( 'new', __( 'New', 'dicestack' ) );
			}
		}

		if ( ! empty( $this->get_setting( 'show_featured', true ) ) && $product->is_featured() ) {
			$badges[] = array( 'feat', __( 'Featured', 'dicestack' ) );
		}

		if ( ! empty( $this->get_setting( 'show_outofstock', true ) ) && ! $product->is_in_stock() ) {
			$badges[] = array( 'oos', __( 'Out of stock', 'dicestack' ) );
		}

		if ( empty( $badges ) ) {
			return;
		}

		echo '<span class="dicestack-badges">';
		foreach ( $badges as $badge ) {
			echo '<span class="dicestack-badge-wc dicestack-b-' . esc_attr( $badge[0] ) . '">' . esc_html( $badge[1] ) . '</span>';
		}
		echo '</span>';
	}
}
