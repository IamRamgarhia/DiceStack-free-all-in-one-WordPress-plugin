<?php
/**
 * Heartbeat Control module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Slows down or disables the WordPress Heartbeat API to cut admin-ajax load on
 * cheap hosting. Replaces the Heartbeat Control plugin / Perfmatters feature.
 */
final class Heartbeat_Control extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'heartbeat_control';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Heartbeat control', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Reduce or disable the Heartbeat API to lower server CPU usage.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'performance';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'bolt';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium performance plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 25,
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
				'key'     => 'frontend',
				'label'   => __( 'Heartbeat on the front end', 'dicestack' ),
				'type'    => 'select',
				'default' => 'disable',
				'options' => array(
					'default' => __( 'Default (keep on)', 'dicestack' ),
					'disable' => __( 'Disable', 'dicestack' ),
				),
			),
			array(
				'key'     => 'interval',
				'label'   => __( 'Admin/editor interval (seconds)', 'dicestack' ),
				'type'    => 'number',
				'default' => 60,
				'min'     => 15,
				'max'     => 300,
				'step'    => 5,
				'help'    => __( 'WordPress default is 15–60s. A higher value means fewer background requests.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		if ( 'disable' === $this->get_setting( 'frontend', 'disable' ) ) {
			add_action( 'init', array( $this, 'maybe_disable_frontend' ), 1 );
		}
		add_filter( 'heartbeat_settings', array( $this, 'set_interval' ) );
	}

	/**
	 * Deregister the heartbeat script on the front end only.
	 *
	 * @return void
	 */
	public function maybe_disable_frontend() {
		if ( ! is_admin() ) {
			wp_deregister_script( 'heartbeat' );
		}
	}

	/**
	 * Apply the configured interval.
	 *
	 * @param array $settings Heartbeat settings.
	 * @return array
	 */
	public function set_interval( $settings ) {
		$interval             = (int) $this->get_setting( 'interval', 60 );
		$settings['interval'] = max( 15, min( 300, $interval ) );
		return $settings;
	}
}
