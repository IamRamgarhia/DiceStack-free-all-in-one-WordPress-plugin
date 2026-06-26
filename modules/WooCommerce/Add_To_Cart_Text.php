<?php
/**
 * WooCommerce Add to Cart Text module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Customises the "Add to cart" button text on shop and product pages.
 */
final class Add_To_Cart_Text extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_add_to_cart_text';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Add to cart button text', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Change the "Add to cart" wording across the shop.', 'dicestack' );
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
			'php_memory_kb' => 10,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 1,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function settings_schema() {
		return array(
			array(
				'key'     => 'text',
				'label'   => __( 'Button text', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'Add to cart', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'text' ) );
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'text' ) );
	}

	/**
	 * Provide the configured button text.
	 *
	 * @param string $default Default text.
	 * @return string
	 */
	public function text( $default ) {
		$value = trim( (string) $this->get_setting( 'text', '' ) );
		return '' !== $value ? $value : $default;
	}
}
