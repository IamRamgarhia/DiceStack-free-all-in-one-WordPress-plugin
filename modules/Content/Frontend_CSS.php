<?php
/**
 * Front-end Custom CSS module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Add custom CSS to the front end without touching the theme. Use it to restyle
 * anything DiceStack outputs (popups, reviews, badges, forms…) so it matches your
 * theme, or to make any other visual tweak. DiceStack front-end elements use
 * .dicestack-* class names that are easy to target.
 */
final class Frontend_CSS extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'frontend_css';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Front-end custom CSS', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add your own CSS to the site front end — restyle DiceStack output to match your theme.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'content';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'code';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'custom-CSS plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 12,
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
				'key'     => 'css',
				'label'   => __( 'Custom CSS', 'dicestack' ),
				'type'    => 'textarea',
				'default' => '',
				'help'    => __( 'Applied site-wide on the front end. Tip: DiceStack elements use .dicestack-* classes (e.g. .dicestack-cookie, .dicestack-reviews, .dicestack-badge-wc).', 'dicestack' ),
			),
		);
	}

	/**
	 * Only administrators may save raw CSS.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function save_settings( array $input ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->get_settings();
		}
		$clean = array( 'css' => isset( $input['css'] ) ? wp_strip_all_tags( (string) $input['css'] ) : '' );
		update_option( $this->settings_option_key(), $clean );
		return $clean;
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_head', array( $this, 'output' ), 999 );
	}

	/**
	 * Print the custom CSS.
	 *
	 * @return void
	 */
	public function output() {
		$css = (string) $this->get_setting( 'css', '' );
		if ( '' !== trim( $css ) ) {
			echo "<style id=\"dicestack-frontend-css\">\n" . wp_strip_all_tags( $css ) . "\n</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS with tags stripped.
		}
	}
}
