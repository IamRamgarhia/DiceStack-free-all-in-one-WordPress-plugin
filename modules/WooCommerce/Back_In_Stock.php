<?php
/**
 * WooCommerce Back-in-Stock module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\WooCommerce;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Lets shoppers subscribe to out-of-stock products and emails them when the
 * item is restocked. Subscribers are stored in product meta and cleared once
 * notified. Replaces back-in-stock notifier extensions.
 */
final class Back_In_Stock extends Abstract_Module {

	/**
	 * Product meta key holding subscriber emails.
	 */
	const META = '_dicestack_bis';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'wc_back_in_stock';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Back-in-stock alerts', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Let customers get an email when an out-of-stock product is available again.', 'dicestack' );
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
		return 'mail';
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
		add_action( 'init', array( $this, 'handle_subscribe' ) );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_form' ), 35 );
		add_action( 'woocommerce_product_set_stock_status', array( $this, 'on_stock_change' ), 10, 3 );
	}

	/**
	 * Show the subscribe form on out-of-stock products.
	 *
	 * @return void
	 */
	public function render_form() {
		global $product;
		if ( ! $product instanceof \WC_Product || $product->is_in_stock() ) {
			return;
		}
		?>
		<form method="post" class="dicestack-bis" style="margin-top:14px;padding:14px;background:#f6f7f9;border-radius:8px;">
			<label style="display:block;margin-bottom:6px;font-weight:500;"><?php esc_html_e( 'Email me when this is back in stock', 'dicestack' ); ?></label>
			<?php wp_nonce_field( 'dicestack_bis', 'dicestack_bis_nonce' ); ?>
			<input type="hidden" name="dicestack_bis_product" value="<?php echo esc_attr( $product->get_id() ); ?>" />
			<input type="email" name="dicestack_bis_email" required placeholder="<?php esc_attr_e( 'you@example.com', 'dicestack' ); ?>" style="padding:8px;width:60%;max-width:280px;" />
			<button type="submit" style="background:#1b2a4a;color:#fff;border:0;padding:9px 16px;border-radius:6px;cursor:pointer;"><?php esc_html_e( 'Notify me', 'dicestack' ); ?></button>
		</form>
		<?php
	}

	/**
	 * Handle a subscribe submission.
	 *
	 * @return void
	 */
	public function handle_subscribe() {
		if ( ! isset( $_POST['dicestack_bis_product'], $_POST['dicestack_bis_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dicestack_bis_nonce'] ) ), 'dicestack_bis' ) ) {
			return;
		}
		$product_id = absint( $_POST['dicestack_bis_product'] );
		$email      = isset( $_POST['dicestack_bis_email'] ) ? sanitize_email( wp_unslash( $_POST['dicestack_bis_email'] ) ) : '';
		if ( ! $product_id || ! is_email( $email ) ) {
			return;
		}

		$subs = get_post_meta( $product_id, self::META, true );
		$subs = is_array( $subs ) ? $subs : array();
		if ( ! in_array( $email, $subs, true ) ) {
			$subs[] = $email;
			update_post_meta( $product_id, self::META, $subs );
		}

		wp_safe_redirect( add_query_arg( 'dicestack_bis', 'ok', get_permalink( $product_id ) ) );
		exit;
	}

	/**
	 * When a product comes back in stock, notify and clear subscribers.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $status     New stock status.
	 * @param mixed  $product    Product object.
	 * @return void
	 */
	public function on_stock_change( $product_id, $status, $product = null ) {
		if ( 'instock' !== $status ) {
			return;
		}
		$subs = get_post_meta( $product_id, self::META, true );
		if ( empty( $subs ) || ! is_array( $subs ) ) {
			return;
		}

		$title = get_the_title( $product_id );
		$url   = get_permalink( $product_id );
		$subject = sprintf(
			/* translators: %s: product name. */
			__( 'Back in stock: %s', 'dicestack' ),
			$title
		);
		$body = sprintf(
			/* translators: 1: product, 2: url. */
			__( "Good news! \"%1\$s\" is back in stock.\n\nBuy it here: %2\$s", 'dicestack' ),
			$title,
			$url
		);

		foreach ( $subs as $email ) {
			if ( is_email( $email ) ) {
				wp_mail( $email, $subject, $body );
			}
		}

		delete_post_meta( $product_id, self::META );
	}
}
