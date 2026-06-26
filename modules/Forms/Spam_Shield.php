<?php
/**
 * Spam Shield module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Forms;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Free, no-API spam protection for the comment and registration forms using a
 * honeypot field and a time trap. The research found CAPTCHA/spam protection is
 * the most-requested paywalled feature — this gives it away free.
 */
final class Spam_Shield extends Abstract_Module {

	/**
	 * Honeypot field name.
	 */
	const HONEYPOT = 'dicestack_hp';

	/**
	 * Timestamp field name.
	 */
	const TS = 'dicestack_cts';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'spam_shield';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Spam shield', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Block comment and registration spam with a honeypot and time trap — no CAPTCHA, no API.', 'dicestack' );
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
		return 'shield';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium spam filters';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 40,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
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
				'key'     => 'protect_comments',
				'label'   => __( 'Protect the comment form', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'protect_registration',
				'label'   => __( 'Protect the registration form', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'min_seconds',
				'label'   => __( 'Minimum seconds to fill the form', 'dicestack' ),
				'type'    => 'number',
				'default' => 3,
				'min'     => 1,
				'max'     => 30,
				'step'    => 1,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		if ( ! empty( $this->get_setting( 'protect_comments', true ) ) ) {
			add_action( 'comment_form_after_fields', array( $this, 'fields' ) );
			add_action( 'comment_form_logged_in_after', array( $this, 'fields' ) );
			add_filter( 'preprocess_comment', array( $this, 'check_comment' ) );
		}

		if ( ! empty( $this->get_setting( 'protect_registration', true ) ) ) {
			add_action( 'register_form', array( $this, 'fields' ) );
			add_filter( 'registration_errors', array( $this, 'check_registration' ) );
		}
	}

	/**
	 * Output the honeypot + timestamp fields.
	 *
	 * @return void
	 */
	public function fields() {
		echo '<div style="position:absolute;left:-9999px;" aria-hidden="true">';
		echo '<input type="text" name="' . esc_attr( self::HONEYPOT ) . '" tabindex="-1" autocomplete="off" value="" />';
		echo '</div>';
		echo '<input type="hidden" name="' . esc_attr( self::TS ) . '" value="' . esc_attr( time() ) . '" />';
	}

	/**
	 * Are the anti-spam fields valid for the current POST?
	 *
	 * @return bool True if the submission looks human.
	 */
	private function passes() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WP comment/registration flows handle their own nonces; we only read our honeypot.
		$honeypot = isset( $_POST[ self::HONEYPOT ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::HONEYPOT ] ) ) : '';
		$ts       = isset( $_POST[ self::TS ] ) ? absint( $_POST[ self::TS ] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( '' !== $honeypot ) {
			return false;
		}

		$min = (int) $this->get_setting( 'min_seconds', 3 );
		if ( $ts > 0 && ( time() - $ts ) < $min ) {
			return false;
		}

		return true;
	}

	/**
	 * Reject spammy comments.
	 *
	 * @param array $commentdata Comment data.
	 * @return array
	 */
	public function check_comment( $commentdata ) {
		// Allow logged-in users and REST/XML-RPC through untouched.
		if ( is_user_logged_in() ) {
			return $commentdata;
		}
		if ( ! $this->passes() ) {
			wp_die(
				esc_html__( 'Your comment was flagged as spam. Please go back and try again.', 'dicestack' ),
				esc_html__( 'Comment blocked', 'dicestack' ),
				array( 'response' => 403, 'back_link' => true )
			);
		}
		return $commentdata;
	}

	/**
	 * Reject spammy registrations.
	 *
	 * @param \WP_Error $errors Registration errors.
	 * @return \WP_Error
	 */
	public function check_registration( $errors ) {
		if ( ! $this->passes() ) {
			$errors->add( 'dicestack_spam', __( 'Registration blocked: the form looked automated.', 'dicestack' ) );
		}
		return $errors;
	}
}
