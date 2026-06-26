<?php
/**
 * WooCommerce Product Schema module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Outputs Product JSON-LD (price, availability, rating) on single product pages
 * for rich results. Replaces the product side of Schema Pro.
 */
final class WC_Product_Schema extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_product_schema';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Product schema', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add Product structured data (price, stock, rating) for shopping rich results.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'seo';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'search';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium schema plugins';
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
			'db_queries'    => 1,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_head', array( $this, 'output' ), 7 );
	}

	/**
	 * Print the product schema.
	 *
	 * @return void
	 */
	public function output() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		$product = wc_get_product( get_queried_object_id() );
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$data = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Product',
			'name'        => $product->get_name(),
			'description' => wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ),
			'sku'         => $product->get_sku(),
			'url'         => get_permalink( $product->get_id() ),
		);

		$image = wp_get_attachment_image_url( $product->get_image_id(), 'large' );
		if ( $image ) {
			$data['image'] = $image;
		}

		$price = $product->get_price();
		if ( '' !== $price ) {
			$data['offers'] = array(
				'@type'         => 'Offer',
				'price'         => wc_format_decimal( $price, wc_get_price_decimals() ),
				'priceCurrency' => get_woocommerce_currency(),
				'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
				'url'           => get_permalink( $product->get_id() ),
			);
		}

		if ( $product->get_review_count() > 0 ) {
			$data['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $product->get_average_rating(),
				'reviewCount' => $product->get_review_count(),
			);
		}

		echo '<script type="application/ld+json">' . wp_json_encode( $data ) . '</script>' . "\n";
	}
}
