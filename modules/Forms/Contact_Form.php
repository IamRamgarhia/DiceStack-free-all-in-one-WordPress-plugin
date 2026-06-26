<?php
/**
 * Contact Form module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Forms;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * A simple, spam-protected contact form via the [dicestack_contact] shortcode.
 * Emails the site owner and (optionally) stores each submission as a private
 * "Form entry" post you can review in wp-admin. Replaces Contact Form 7 basics.
 */
final class Contact_Form extends Abstract_Module {

	/**
	 * Custom post type used to store submissions.
	 */
	const CPT = 'dicestack_entry';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'contact_form';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Contact form', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'A spam-protected contact form via [dicestack_contact]. Emails you and saves each entry.', 'dicestack' );
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
		return 'forms';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium form builders';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 120,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0.4,
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
				'key'     => 'recipient',
				'label'   => __( 'Send submissions to', 'dicestack' ),
				'type'    => 'text',
				'default' => get_option( 'admin_email' ),
				'help'    => __( 'Email address that receives form submissions.', 'dicestack' ),
			),
			array(
				'key'     => 'success_message',
				'label'   => __( 'Success message', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'Thanks! Your message has been sent.', 'dicestack' ),
			),
			array(
				'key'     => 'store_entries',
				'label'   => __( 'Save submissions in the dashboard', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( 'init', array( $this, 'maybe_handle_submission' ) );
		add_shortcode( 'dicestack_contact', array( $this, 'render_form' ) );

		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( $this, 'add_entry_meta_box' ) );
		}
	}

	/**
	 * Register the private "Form entry" post type.
	 *
	 * @return void
	 */
	public function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'          => array(
					'name'          => __( 'Form entries', 'dicestack' ),
					'singular_name' => __( 'Form entry', 'dicestack' ),
					'menu_name'     => __( 'Form entries', 'dicestack' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'dicestack',
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
				'supports'        => array( 'title' ),
				'menu_icon'       => 'dashicons-email',
			)
		);
	}

	/**
	 * Render the contact form.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_form( $atts ) {
		$sent = isset( $_GET['dicestack_sent'] ) ? sanitize_text_field( wp_unslash( $_GET['dicestack_sent'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flash flag.

		ob_start();

		if ( '1' === $sent ) {
			echo '<div class="dicestack-form-success" style="background:#eaf3de;color:#3b6d11;padding:12px 16px;border-radius:8px;margin-bottom:16px;">' . esc_html( $this->get_setting( 'success_message', __( 'Thanks! Your message has been sent.', 'dicestack' ) ) ) . '</div>';
		} elseif ( 'error' === $sent ) {
			echo '<div class="dicestack-form-error" style="background:#fcebeb;color:#a32d2d;padding:12px 16px;border-radius:8px;margin-bottom:16px;">' . esc_html__( 'Sorry, your message could not be sent. Please check your entries and try again.', 'dicestack' ) . '</div>';
		}
		?>
		<form class="dicestack-contact-form" method="post" action="" style="max-width:560px;">
			<?php wp_nonce_field( 'dicestack_contact', 'dicestack_contact_nonce' ); ?>
			<input type="hidden" name="dicestack_action" value="dicestack_contact_submit" />
			<input type="hidden" name="dicestack_ts" value="<?php echo esc_attr( time() ); ?>" />
			<div aria-hidden="true" style="position:absolute;left:-9999px;">
				<label><?php esc_html_e( 'Leave this field empty', 'dicestack' ); ?>
					<input type="text" name="dicestack_website" tabindex="-1" autocomplete="off" value="" />
				</label>
			</div>
			<p>
				<label for="dicestack_name"><?php esc_html_e( 'Name', 'dicestack' ); ?></label><br />
				<input type="text" id="dicestack_name" name="dicestack_name" required style="width:100%;padding:8px;" />
			</p>
			<p>
				<label for="dicestack_email"><?php esc_html_e( 'Email', 'dicestack' ); ?></label><br />
				<input type="email" id="dicestack_email" name="dicestack_email" required style="width:100%;padding:8px;" />
			</p>
			<p>
				<label for="dicestack_subject"><?php esc_html_e( 'Subject', 'dicestack' ); ?></label><br />
				<input type="text" id="dicestack_subject" name="dicestack_subject" style="width:100%;padding:8px;" />
			</p>
			<p>
				<label for="dicestack_message"><?php esc_html_e( 'Message', 'dicestack' ); ?></label><br />
				<textarea id="dicestack_message" name="dicestack_message" rows="6" required style="width:100%;padding:8px;"></textarea>
			</p>
			<p>
				<button type="submit" style="background:#1b2a4a;color:#fff;border:0;padding:10px 20px;border-radius:8px;cursor:pointer;"><?php esc_html_e( 'Send message', 'dicestack' ); ?></button>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle a posted submission (PRG pattern).
	 *
	 * @return void
	 */
	public function maybe_handle_submission() {
		if ( ! isset( $_POST['dicestack_action'] ) || 'dicestack_contact_submit' !== $_POST['dicestack_action'] ) {
			return;
		}
		if ( ! isset( $_POST['dicestack_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dicestack_contact_nonce'] ) ), 'dicestack_contact' ) ) {
			$this->redirect_back( 'error' );
		}

		// Spam checks: honeypot must be empty, and form must take >= 3s to fill.
		$honeypot = isset( $_POST['dicestack_website'] ) ? sanitize_text_field( wp_unslash( $_POST['dicestack_website'] ) ) : '';
		$ts       = isset( $_POST['dicestack_ts'] ) ? absint( $_POST['dicestack_ts'] ) : 0;
		if ( '' !== $honeypot || ( $ts && ( time() - $ts ) < 3 ) ) {
			// Silently treat as success to avoid tipping off bots.
			$this->redirect_back( '1' );
		}

		$name    = isset( $_POST['dicestack_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dicestack_name'] ) ) : '';
		$email   = isset( $_POST['dicestack_email'] ) ? sanitize_email( wp_unslash( $_POST['dicestack_email'] ) ) : '';
		$subject = isset( $_POST['dicestack_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['dicestack_subject'] ) ) : '';
		$message = isset( $_POST['dicestack_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['dicestack_message'] ) ) : '';

		if ( '' === $name || ! is_email( $email ) || '' === $message ) {
			$this->redirect_back( 'error' );
		}

		$subject = '' !== $subject ? $subject : __( 'New contact form submission', 'dicestack' );

		// Email the site owner.
		$recipient = sanitize_email( (string) $this->get_setting( 'recipient', get_option( 'admin_email' ) ) );
		$recipient = is_email( $recipient ) ? $recipient : get_option( 'admin_email' );
		$body      = sprintf(
			"%s: %s\n%s: %s\n\n%s\n",
			__( 'Name', 'dicestack' ),
			$name,
			__( 'Email', 'dicestack' ),
			$email,
			$message
		);
		$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );
		wp_mail( $recipient, '[' . get_bloginfo( 'name' ) . '] ' . $subject, $body, $headers );

		// Store the entry.
		if ( ! empty( $this->get_setting( 'store_entries', true ) ) ) {
			$entry_id = wp_insert_post(
				array(
					'post_type'   => self::CPT,
					'post_status' => 'publish',
					'post_title'  => $subject,
				)
			);
			if ( $entry_id && ! is_wp_error( $entry_id ) ) {
				update_post_meta( $entry_id, '_dicestack_name', $name );
				update_post_meta( $entry_id, '_dicestack_email', $email );
				update_post_meta( $entry_id, '_dicestack_message', $message );
				update_post_meta( $entry_id, '_dicestack_ip', $this->get_ip() );
			}
		}

		$this->redirect_back( '1' );
	}

	/**
	 * Redirect back to the referring page with a status flag.
	 *
	 * @param string $status Status flag ('1' or 'error').
	 * @return void
	 */
	private function redirect_back( $status ) {
		$ref = wp_get_referer();
		$url = $ref ? $ref : home_url( '/' );
		wp_safe_redirect( add_query_arg( 'dicestack_sent', $status, remove_query_arg( 'dicestack_sent', $url ) ) );
		exit;
	}

	/**
	 * Client IP (best effort).
	 *
	 * @return string
	 */
	private function get_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/**
	 * Show submission details in the entry editor.
	 *
	 * @return void
	 */
	public function add_entry_meta_box() {
		add_meta_box(
			'dicestack_entry_details',
			__( 'Submission details', 'dicestack' ),
			array( $this, 'render_entry_meta_box' ),
			self::CPT,
			'normal',
			'high'
		);
	}

	/**
	 * Render the entry detail meta box.
	 *
	 * @param \WP_Post $post Entry post.
	 * @return void
	 */
	public function render_entry_meta_box( $post ) {
		$name    = get_post_meta( $post->ID, '_dicestack_name', true );
		$email   = get_post_meta( $post->ID, '_dicestack_email', true );
		$message = get_post_meta( $post->ID, '_dicestack_message', true );
		$ip      = get_post_meta( $post->ID, '_dicestack_ip', true );
		echo '<p><strong>' . esc_html__( 'Name', 'dicestack' ) . ':</strong> ' . esc_html( $name ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Email', 'dicestack' ) . ':</strong> <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></p>';
		echo '<p><strong>' . esc_html__( 'Message', 'dicestack' ) . ':</strong></p>';
		echo '<div style="white-space:pre-wrap;background:#f6f7f9;padding:12px;border-radius:6px;">' . esc_html( $message ) . '</div>';
		if ( $ip ) {
			echo '<p style="color:#6b7280;margin-top:10px;"><strong>' . esc_html__( 'IP', 'dicestack' ) . ':</strong> ' . esc_html( $ip ) . '</p>';
		}
	}
}
