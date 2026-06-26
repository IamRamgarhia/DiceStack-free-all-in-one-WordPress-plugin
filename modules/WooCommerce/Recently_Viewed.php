<?php
/**
 * WooCommerce Recently Viewed Products module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks products a visitor has viewed (in a cookie) and shows them via the
 * [dicestack_recently_viewed] shortcode. Replaces YITH Recently Viewed.
 */
final class Recently_Viewed extends Abstract_Module {

	/**
	 * Cookie name.
	 */
	const COOKIE = 'dicestack_recently_viewed';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_recently_viewed';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Recently viewed products', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Show shoppers the products they recently looked at via [dicestack_recently_viewed].', 'dicestack' );
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
		return 'premium recently-viewed plugins';
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
			'php_memory_kb' => 40,
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
				'key'     => 'count',
				'label'   => __( 'Number of products to show', 'dicestack' ),
				'type'    => 'number',
				'default' => 4,
				'min'     => 1,
				'max'     => 12,
				'step'    => 1,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'template_redirect', array( $this, 'track' ) );
		add_shortcode( 'dicestack_recently_viewed', array( $this, 'render' ) );
	}

	/**
	 * Record the current product in the cookie.
	 *
	 * @return void
	 */
	public function track() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		$id  = get_queried_object_id();
		$ids = $this->get_ids();

		// Move to front, de-dupe, cap at 15 stored.
		$ids = array_values( array_diff( $ids, array( $id ) ) );
		array_unshift( $ids, $id );
		$ids = array_slice( $ids, 0, 15 );

		if ( ! headers_sent() ) {
			setcookie( self::COOKIE, implode( ',', $ids ), time() + WEEK_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
		}
		// Make it available immediately for the current request.
		$_COOKIE[ self::COOKIE ] = implode( ',', $ids );
	}

	/**
	 * Read stored product IDs from the cookie.
	 *
	 * @return int[]
	 */
	private function get_ids() {
		if ( empty( $_COOKIE[ self::COOKIE ] ) ) {
			return array();
		}
		$raw = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) );
		$ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Render the recently-viewed products grid.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$ids = $this->get_ids();
		if ( empty( $ids ) ) {
			return '';
		}

		// Exclude the product currently being viewed.
		if ( function_exists( 'is_product' ) && is_product() ) {
			$ids = array_values( array_diff( $ids, array( get_queried_object_id() ) ) );
		}
		if ( empty( $ids ) ) {
			return '';
		}

		$count = (int) $this->get_setting( 'count', 4 );
		$ids   = array_slice( $ids, 0, $count );

		$shortcode = sprintf( '[products ids="%s" columns="%d" limit="%d"]', esc_attr( implode( ',', $ids ) ), min( 4, $count ), $count );

		return '<div class="dicestack-recently-viewed"><h3>' . esc_html__( 'Recently viewed', 'dicestack' ) . '</h3>' . do_shortcode( $shortcode ) . '</div>';
	}
}
