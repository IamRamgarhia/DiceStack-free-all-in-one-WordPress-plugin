<?php
/**
 * Header & Footer Code module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Inject tracking/verification code into the head or footer without editing
 * theme files (so it survives theme updates). Replaces "Insert Headers and
 * Footers" / Header Footer Code Manager.
 */
final class Header_Footer_Code extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'header_footer_code';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Header & footer code', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add analytics, verification, or custom scripts to the head or footer safely.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'admin';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'file-code';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'header/footer code plugins';
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
	public function settings_schema() {
		return array(
			array(
				'key'     => 'head_code',
				'label'   => __( 'Code in <head>', 'dicestack' ),
				'type'    => 'textarea',
				'default' => '',
				'help'    => __( 'Pasted verbatim into the page head. Only admins can edit this field.', 'dicestack' ),
			),
			array(
				'key'     => 'footer_code',
				'label'   => __( 'Code before </body>', 'dicestack' ),
				'type'    => 'textarea',
				'default' => '',
				'help'    => __( 'Pasted verbatim before the closing body tag.', 'dicestack' ),
			),
		);
	}

	/**
	 * Override sanitisation: this field intentionally stores raw script markup,
	 * but only users with unfiltered_html (admins) may save it.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function save_settings( array $input ) {
		if ( ! current_user_can( 'unfiltered_html' ) && ! current_user_can( 'manage_options' ) ) {
			return $this->get_settings();
		}
		$clean = array(
			'head_code'   => isset( $input['head_code'] ) ? (string) $input['head_code'] : '',
			'footer_code' => isset( $input['footer_code'] ) ? (string) $input['footer_code'] : '',
		);
		update_option( $this->settings_option_key(), $clean );
		return $clean;
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_head', array( $this, 'print_head' ), 99 );
		add_action( 'wp_footer', array( $this, 'print_footer' ), 99 );
	}

	/**
	 * Print head code.
	 *
	 * @return void
	 */
	public function print_head() {
		$code = (string) $this->get_setting( 'head_code', '' );
		if ( '' !== trim( $code ) ) {
			// Intentionally unescaped: admin-authored markup (gated by unfiltered_html on save).
			echo "\n" . $code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Print footer code.
	 *
	 * @return void
	 */
	public function print_footer() {
		$code = (string) $this->get_setting( 'footer_code', '' );
		if ( '' !== trim( $code ) ) {
			// Intentionally unescaped: admin-authored markup (gated by unfiltered_html on save).
			echo "\n" . $code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
