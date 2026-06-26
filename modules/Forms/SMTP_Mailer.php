<?php
/**
 * SMTP Mailer module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Forms;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Routes wp_mail() through an authenticated SMTP server so emails actually
 * deliver instead of landing in spam. Uses WordPress's bundled PHPMailer (no
 * extra library). Replaces WP Mail SMTP.
 */
final class SMTP_Mailer extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'smtp_mailer';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'SMTP email', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Send WordPress email through your own SMTP server so it lands in the inbox.', 'dicestack' );
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
		return 'premium SMTP plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 40,
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
				'key'     => 'host',
				'label'   => __( 'SMTP host', 'dicestack' ),
				'type'    => 'text',
				'default' => '',
				'help'    => __( 'e.g. smtp.gmail.com, smtp.mailgun.org', 'dicestack' ),
			),
			array(
				'key'     => 'port',
				'label'   => __( 'Port', 'dicestack' ),
				'type'    => 'number',
				'default' => 587,
				'min'     => 1,
				'max'     => 65535,
				'step'    => 1,
			),
			array(
				'key'     => 'encryption',
				'label'   => __( 'Encryption', 'dicestack' ),
				'type'    => 'select',
				'default' => 'tls',
				'options' => array(
					'tls'  => __( 'TLS', 'dicestack' ),
					'ssl'  => __( 'SSL', 'dicestack' ),
					'none' => __( 'None', 'dicestack' ),
				),
			),
			array(
				'key'     => 'username',
				'label'   => __( 'SMTP username', 'dicestack' ),
				'type'    => 'text',
				'default' => '',
			),
			array(
				'key'     => 'password',
				'label'   => __( 'SMTP password', 'dicestack' ),
				'type'    => 'password',
				'default' => '',
				'help'    => __( 'Stored in your database. Use an app password where possible.', 'dicestack' ),
			),
			array(
				'key'     => 'from_email',
				'label'   => __( 'From email', 'dicestack' ),
				'type'    => 'text',
				'default' => get_option( 'admin_email' ),
			),
			array(
				'key'     => 'from_name',
				'label'   => __( 'From name', 'dicestack' ),
				'type'    => 'text',
				'default' => get_option( 'blogname' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'phpmailer_init', array( $this, 'configure' ) );
		add_filter( 'wp_mail_from', array( $this, 'from_email' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'from_name' ) );
	}

	/**
	 * Apply SMTP settings to PHPMailer.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
	 * @return void
	 */
	public function configure( $phpmailer ) {
		$s = $this->get_settings();
		if ( empty( $s['host'] ) ) {
			return; // Not configured yet; leave WordPress defaults.
		}

		$phpmailer->isSMTP();
		$phpmailer->Host = $s['host']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$phpmailer->Port = (int) $s['port']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		if ( 'none' !== $s['encryption'] ) {
			$phpmailer->SMTPSecure = $s['encryption']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}

		if ( ! empty( $s['username'] ) ) {
			$phpmailer->SMTPAuth = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$phpmailer->Username = $s['username']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$phpmailer->Password = $s['password']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}
	}

	/**
	 * Override the from address when configured.
	 *
	 * @param string $email Default from email.
	 * @return string
	 */
	public function from_email( $email ) {
		$from = sanitize_email( (string) $this->get_setting( 'from_email', '' ) );
		return is_email( $from ) ? $from : $email;
	}

	/**
	 * Override the from name when configured.
	 *
	 * @param string $name Default from name.
	 * @return string
	 */
	public function from_name( $name ) {
		$from = (string) $this->get_setting( 'from_name', '' );
		return '' !== trim( $from ) ? $from : $name;
	}
}
