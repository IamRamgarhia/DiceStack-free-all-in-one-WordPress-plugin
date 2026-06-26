<?php
/**
 * Newsletter Signup module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Forms;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * A self-hosted email capture form via [dicestack_subscribe]. Stores subscribers
 * as a private post type you can export — no third-party service required.
 */
final class Newsletter_Signup extends Abstract_Module {

	/**
	 * Subscriber post type.
	 */
	const CPT = 'dicestack_subscriber';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'newsletter_signup';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Newsletter signup', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Collect email subscribers with [dicestack_subscribe] — stored on your own site.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'forms';
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
	public function replaces() {
		return 'premium signup-form plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 40,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0.3,
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
				'key'     => 'heading',
				'label'   => __( 'Form heading', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'Subscribe to our newsletter', 'dicestack' ),
			),
			array(
				'key'     => 'success',
				'label'   => __( 'Success message', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'Thanks for subscribing!', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( 'init', array( $this, 'handle' ) );
		add_shortcode( 'dicestack_subscribe', array( $this, 'render' ) );
	}

	/**
	 * Register the subscriber post type.
	 *
	 * @return void
	 */
	public function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'          => array(
					'name'          => __( 'Subscribers', 'dicestack' ),
					'singular_name' => __( 'Subscriber', 'dicestack' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'dicestack',
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
				'menu_icon'       => 'dashicons-email-alt',
			)
		);
	}

	/**
	 * Handle a subscribe submission.
	 *
	 * @return void
	 */
	public function handle() {
		if ( ! isset( $_POST['dicestack_subscribe_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dicestack_subscribe_nonce'] ) ), 'dicestack_subscribe' ) ) {
			return;
		}
		// Honeypot.
		if ( ! empty( $_POST['dicestack_sub_hp'] ) ) {
			$this->redirect( 'ok' );
		}
		$email = isset( $_POST['dicestack_sub_email'] ) ? sanitize_email( wp_unslash( $_POST['dicestack_sub_email'] ) ) : '';
		if ( ! is_email( $email ) ) {
			$this->redirect( 'error' );
		}

		// De-dupe by email (stored as the post title). get_page_by_title() is
		// deprecated in WP 6.2+, so query directly.
		$existing = get_posts(
			array(
				'post_type'        => self::CPT,
				'post_status'      => 'any',
				'title'            => $email,
				'numberposts'      => 1,
				'fields'           => 'ids',
			)
		);
		if ( empty( $existing ) ) {
			wp_insert_post(
				array(
					'post_type'   => self::CPT,
					'post_status' => 'publish',
					'post_title'  => $email,
				)
			);
		}
		$this->redirect( 'ok' );
	}

	/**
	 * Redirect back with a status flag.
	 *
	 * @param string $status Status.
	 * @return void
	 */
	private function redirect( $status ) {
		$ref = wp_get_referer();
		wp_safe_redirect( add_query_arg( 'dicestack_sub', $status, $ref ? $ref : home_url( '/' ) ) );
		exit;
	}

	/**
	 * Render the signup form.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$status = isset( $_GET['dicestack_sub'] ) ? sanitize_text_field( wp_unslash( $_GET['dicestack_sub'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flash flag.
		ob_start();
		if ( 'ok' === $status ) {
			echo '<div style="background:#eaf3de;color:#3b6d11;padding:10px 14px;border-radius:8px;margin-bottom:12px;">' . esc_html( $this->get_setting( 'success', __( 'Thanks for subscribing!', 'dicestack' ) ) ) . '</div>';
		}
		?>
		<form method="post" class="dicestack-subscribe" style="max-width:420px;">
			<?php wp_nonce_field( 'dicestack_subscribe', 'dicestack_subscribe_nonce' ); ?>
			<strong style="display:block;margin-bottom:8px;"><?php echo esc_html( $this->get_setting( 'heading', __( 'Subscribe to our newsletter', 'dicestack' ) ) ); ?></strong>
			<div style="position:absolute;left:-9999px;" aria-hidden="true"><input type="text" name="dicestack_sub_hp" tabindex="-1" autocomplete="off" /></div>
			<div style="display:flex;gap:8px;">
				<input type="email" name="dicestack_sub_email" required placeholder="<?php esc_attr_e( 'you@example.com', 'dicestack' ); ?>" style="flex:1;padding:9px;" />
				<button type="submit" style="background:#1b2a4a;color:#fff;border:0;padding:9px 18px;border-radius:6px;cursor:pointer;"><?php esc_html_e( 'Subscribe', 'dicestack' ); ?></button>
			</div>
		</form>
		<?php
		return ob_get_clean();
	}
}
