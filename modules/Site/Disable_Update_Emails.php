<?php
/**
 * Disable Update Emails module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Site;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Silences the automatic-update result emails WordPress sends after background
 * updates, for owners who don't want their inbox filled.
 */
final class Disable_Update_Emails extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'disable_update_emails';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Silence auto-update emails', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Stop the result emails WordPress sends after automatic updates.', 'dicestack' );
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
				'key'     => 'keep_critical',
				'label'   => __( 'Still send emails for failed updates', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'auto_plugin_update_send_email', array( $this, 'filter_result' ), 10, 2 );
		add_filter( 'auto_theme_update_send_email', array( $this, 'filter_result' ), 10, 2 );
		add_filter( 'auto_core_update_send_email', array( $this, 'filter_core' ), 10, 4 );
	}

	/**
	 * Decide whether to send plugin/theme update emails.
	 *
	 * @param bool  $send   Whether to send.
	 * @param mixed $update Update context.
	 * @return bool
	 */
	public function filter_result( $send, $update = null ) {
		// $update of type 'fail'/'success' isn't passed here uniformly; default off.
		return false;
	}

	/**
	 * Decide whether to send core update emails (keep failures if configured).
	 *
	 * @param bool   $send Whether to send.
	 * @param string $type Email type (success, fail, critical, manual).
	 * @param object $core Core update object.
	 * @param mixed  $result Result.
	 * @return bool
	 */
	public function filter_core( $send, $type, $core = null, $result = null ) {
		if ( ! empty( $this->get_setting( 'keep_critical', true ) ) && in_array( $type, array( 'fail', 'critical' ), true ) ) {
			return true;
		}
		return false;
	}
}
