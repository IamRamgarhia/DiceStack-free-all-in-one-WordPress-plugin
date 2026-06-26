<?php
/**
 * Mail From module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Site;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Sets a friendly From name and address for outgoing WordPress mail (instead of
 * the default "WordPress <wordpress@yourdomain>"). Works with or without SMTP.
 */
final class Mail_From extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'mail_from';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Email sender name', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Set the From name and address used on all WordPress emails.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'site';
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
	public function performance_profile() {
		return array(
			'php_memory_kb' => 10,
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
				'key'     => 'from_name',
				'label'   => __( 'From name', 'dicestack' ),
				'type'    => 'text',
				'default' => get_option( 'blogname' ),
			),
			array(
				'key'     => 'from_email',
				'label'   => __( 'From email', 'dicestack' ),
				'type'    => 'text',
				'default' => '',
				'help'    => __( 'Leave blank to keep the WordPress default address.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'wp_mail_from_name', array( $this, 'name_filter' ) );
		add_filter( 'wp_mail_from', array( $this, 'email_filter' ) );
	}

	/**
	 * Filter the From name.
	 *
	 * @param string $name Default name.
	 * @return string
	 */
	public function name_filter( $name ) {
		$value = trim( (string) $this->get_setting( 'from_name', '' ) );
		return '' !== $value ? $value : $name;
	}

	/**
	 * Filter the From address.
	 *
	 * @param string $email Default email.
	 * @return string
	 */
	public function email_filter( $email ) {
		$value = sanitize_email( (string) $this->get_setting( 'from_email', '' ) );
		return is_email( $value ) ? $value : $email;
	}
}
