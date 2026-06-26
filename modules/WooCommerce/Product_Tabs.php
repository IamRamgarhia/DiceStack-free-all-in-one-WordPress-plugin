<?php
/**
 * WooCommerce Custom Product Tabs module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a global extra tab (e.g. Shipping & Returns) to every product page.
 * Replaces Barn2 Product Tabs for the global-tab case.
 */
final class Product_Tabs extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_product_tabs';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Custom product tab', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add a shared tab (such as Shipping & Returns) to every product page.', 'dicestack' );
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
		return 'forms';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium product-tab plugins';
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
				'key'     => 'title',
				'label'   => __( 'Tab title', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'Shipping & Returns', 'dicestack' ),
			),
			array(
				'key'     => 'content',
				'label'   => __( 'Tab content', 'dicestack' ),
				'type'    => 'textarea',
				'default' => __( 'We ship worldwide within 2–4 business days. Returns accepted within 30 days.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'woocommerce_product_tabs', array( $this, 'add_tab' ) );
	}

	/**
	 * Register the custom tab.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array
	 */
	public function add_tab( $tabs ) {
		$title = (string) $this->get_setting( 'title', '' );
		if ( '' === trim( $title ) ) {
			return $tabs;
		}
		$tabs['dicestack_tab'] = array(
			'title'    => $title,
			'priority' => 50,
			'callback' => array( $this, 'render' ),
		);
		return $tabs;
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render() {
		echo wpautop( wp_kses_post( (string) $this->get_setting( 'content', '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post sanitises.
	}
}
