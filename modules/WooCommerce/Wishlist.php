<?php
/**
 * WooCommerce Wishlist module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * A simple, no-AJAX wishlist. Logged-in users' lists are stored in user meta;
 * guests use a cookie. Display the list with [dicestack_wishlist]. Replaces YITH
 * Wishlist for the core use case.
 */
final class Wishlist extends Abstract_Module {

	/**
	 * User-meta / cookie key.
	 */
	const KEY = 'dicestack_wishlist';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_wishlist';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Wishlist', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Let shoppers save products to a wishlist. Show it with [dicestack_wishlist].', 'dicestack' );
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
		return 'premium wishlist plugins';
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
			'front_css_kb'  => 0,
			'db_queries'    => 1,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'init', array( $this, 'handle_toggle' ) );
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'button' ) );
		add_action( 'woocommerce_after_shop_loop_item', array( $this, 'button' ), 20 );
		add_shortcode( 'dicestack_wishlist', array( $this, 'render_list' ) );
	}

	/**
	 * Read the current wishlist IDs.
	 *
	 * @return int[]
	 */
	private function get_list() {
		if ( is_user_logged_in() ) {
			$list = get_user_meta( get_current_user_id(), self::KEY, true );
		} else {
			$list = isset( $_COOKIE[ self::KEY ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::KEY ] ) ) : '';
			$list = $list ? explode( ',', $list ) : array();
		}
		return array_values( array_unique( array_filter( array_map( 'absint', (array) $list ) ) ) );
	}

	/**
	 * Persist the wishlist IDs.
	 *
	 * @param int[] $list Product IDs.
	 * @return void
	 */
	private function save_list( $list ) {
		$list = array_values( array_unique( array_filter( array_map( 'absint', $list ) ) ) );
		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), self::KEY, $list );
		} elseif ( ! headers_sent() ) {
			setcookie( self::KEY, implode( ',', $list ), time() + MONTH_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
			$_COOKIE[ self::KEY ] = implode( ',', $list );
		}
	}

	/**
	 * Handle add/remove requests.
	 *
	 * @return void
	 */
	public function handle_toggle() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified below before any state change.
		if ( ! isset( $_GET['dicestack_wishlist'], $_GET['product'] ) ) {
			return;
		}
		$action  = sanitize_key( wp_unslash( $_GET['dicestack_wishlist'] ) );
		$product = absint( $_GET['product'] );
		$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'dicestack_wishlist_' . $product ) ) {
			return;
		}

		$list = $this->get_list();
		if ( 'add' === $action ) {
			$list[] = $product;
		} elseif ( 'remove' === $action ) {
			$list = array_diff( $list, array( $product ) );
		}
		$this->save_list( $list );

		wp_safe_redirect( remove_query_arg( array( 'dicestack_wishlist', 'product', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * Render the add/remove button for the current product.
	 *
	 * @return void
	 */
	public function button() {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$id     = $product->get_id();
		$list   = $this->get_list();
		$in     = in_array( $id, $list, true );
		$action = $in ? 'remove' : 'add';
		$label  = $in ? __( '♥ In wishlist — remove', 'dicestack' ) : __( '♡ Add to wishlist', 'dicestack' );
		$url    = wp_nonce_url(
			add_query_arg(
				array(
					'dicestack_wishlist' => $action,
					'product'         => $id,
				)
			),
			'dicestack_wishlist_' . $id
		);
		echo '<a class="dicestack-wishlist-btn" href="' . esc_url( $url ) . '" style="display:inline-block;margin-top:8px;font-size:13px;color:#d4537e;text-decoration:none;">' . esc_html( $label ) . '</a>';
	}

	/**
	 * Render the wishlist contents.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_list( $atts ) {
		$list = $this->get_list();
		if ( empty( $list ) ) {
			return '<p>' . esc_html__( 'Your wishlist is empty.', 'dicestack' ) . '</p>';
		}
		$shortcode = sprintf( '[products ids="%s" columns="4"]', esc_attr( implode( ',', $list ) ) );
		return do_shortcode( $shortcode );
	}
}
