<?php
/**
 * Admin Branding (white-label) module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * White-labels the admin footer text and optionally the howdy greeting.
 * Defaults carry Dice Codes branding; agencies can set their own.
 */
final class Admin_Branding extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'admin_branding';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Admin branding', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Set a custom admin footer credit for a polished, branded dashboard.', 'dicestack' );
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
		return 'tool';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium white-label plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 14,
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
				'key'     => 'footer_text',
				'label'   => __( 'Admin footer text', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'Built with DiceStack by Dice Codes', 'dicestack' ),
			),
			array(
				'key'     => 'footer_link',
				'label'   => __( 'Footer link URL', 'dicestack' ),
				'type'    => 'url',
				'default' => 'https://dicecodes.com',
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'admin_footer_text', array( $this, 'footer' ), 100 );
	}

	/**
	 * Replace the admin footer credit.
	 *
	 * @param string $text Existing footer text.
	 * @return string
	 */
	public function footer( $text ) {
		$label = trim( (string) $this->get_setting( 'footer_text', '' ) );
		if ( '' === $label ) {
			return $text;
		}
		$link = esc_url( (string) $this->get_setting( 'footer_link', '' ) );
		if ( $link ) {
			return '<span id="footer-thankyou"><a href="' . $link . '" target="_blank" rel="noopener">' . esc_html( $label ) . '</a></span>';
		}
		return '<span id="footer-thankyou">' . esc_html( $label ) . '</span>';
	}
}
