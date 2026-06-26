<?php
/**
 * Terms Consent module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Forms;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a required "I agree to the terms" checkbox to the comment and
 * registration forms, with a configurable link to your policy page.
 */
final class Terms_Consent extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'terms_consent';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Terms consent checkbox', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Require visitors to agree to your terms before commenting or registering.', 'dicestack' );
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
	public function performance_profile() {
		return array(
			'php_memory_kb' => 16,
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
				'key'     => 'label',
				'label'   => __( 'Checkbox label', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'I agree to the terms and privacy policy.', 'dicestack' ),
			),
			array(
				'key'     => 'policy_url',
				'label'   => __( 'Policy page URL (optional)', 'dicestack' ),
				'type'    => 'url',
				'default' => '',
			),
			array(
				'key'     => 'comments',
				'label'   => __( 'Show on comment form', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'registration',
				'label'   => __( 'Show on registration form', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		if ( ! empty( $this->get_setting( 'comments', true ) ) ) {
			add_filter( 'comment_form_submit_field', array( $this, 'comment_field' ), 10 );
			add_filter( 'preprocess_comment', array( $this, 'verify_comment' ) );
		}
		if ( ! empty( $this->get_setting( 'registration', true ) ) ) {
			add_action( 'register_form', array( $this, 'register_field' ) );
			add_filter( 'registration_errors', array( $this, 'verify_registration' ) );
		}
	}

	/**
	 * Build the checkbox HTML.
	 *
	 * @return string
	 */
	private function checkbox_html() {
		$label = esc_html( (string) $this->get_setting( 'label', '' ) );
		$url   = esc_url( (string) $this->get_setting( 'policy_url', '' ) );
		if ( $url ) {
			$label .= ' <a href="' . $url . '" target="_blank" rel="noopener">' . esc_html__( '(read more)', 'dicestack' ) . '</a>';
		}
		return '<p class="dicestack-terms"><label><input type="checkbox" name="dicestack_terms" value="1" /> ' . $label . '</label></p>';
	}

	/**
	 * Prepend the checkbox before the comment submit button.
	 *
	 * @param string $submit_field Submit field HTML.
	 * @return string
	 */
	public function comment_field( $submit_field ) {
		return $this->checkbox_html() . $submit_field;
	}

	/**
	 * Output the checkbox on the registration form.
	 *
	 * @return void
	 */
	public function register_field() {
		echo $this->checkbox_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder.
	}

	/**
	 * Block comments without consent.
	 *
	 * @param array $commentdata Comment data.
	 * @return array
	 */
	public function verify_comment( $commentdata ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WP comment flow nonce covers this; reading our checkbox only.
		if ( empty( $_POST['dicestack_terms'] ) ) {
			wp_die(
				esc_html__( 'You must agree to the terms to comment.', 'dicestack' ),
				esc_html__( 'Consent required', 'dicestack' ),
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}
		return $commentdata;
	}

	/**
	 * Block registration without consent.
	 *
	 * @param \WP_Error $errors Errors.
	 * @return \WP_Error
	 */
	public function verify_registration( $errors ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WP registration flow handles nonce; reading our checkbox only.
		if ( empty( $_POST['dicestack_terms'] ) ) {
			$errors->add( 'dicestack_terms', __( 'You must agree to the terms to register.', 'dicestack' ) );
		}
		return $errors;
	}
}
