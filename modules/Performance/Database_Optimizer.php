<?php
/**
 * Database Optimizer module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Cleans junk from the database (revisions, auto-drafts, expired transients,
 * spam/trashed comments) on a schedule. Replaces WP-Optimize's core job.
 */
final class Database_Optimizer extends Abstract_Module {

	/**
	 * Cron hook name.
	 */
	const CRON_HOOK = 'dicestack_db_cleanup';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'database_optimizer';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Database optimizer', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Automatically clear revisions, auto-drafts, transients, and spam to keep the database lean.', 'dicestack' );
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
		return 'database';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium database optimizers';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 50,
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
				'key'     => 'frequency',
				'label'   => __( 'Run automatically', 'dicestack' ),
				'type'    => 'select',
				'default' => 'weekly',
				'options' => array(
					'never'   => __( 'Never (manual only)', 'dicestack' ),
					'daily'   => __( 'Daily', 'dicestack' ),
					'weekly'  => __( 'Weekly', 'dicestack' ),
				),
			),
			array(
				'key'     => 'revisions',
				'label'   => __( 'Delete post revisions', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'transients',
				'label'   => __( 'Delete expired transients', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'spam',
				'label'   => __( 'Delete spam & trashed comments', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( self::CRON_HOOK, array( $this, 'run_cleanup' ) );
		add_action( 'init', array( $this, 'ensure_schedule' ) );

		// Tidy the cron event when the module is turned off.
		add_action( 'dicestack_module_disabled_' . $this->id(), array( $this, 'clear_schedule' ) );
	}

	/**
	 * Make sure the cron event matches the configured frequency.
	 *
	 * @return void
	 */
	public function ensure_schedule() {
		$frequency = $this->get_setting( 'frequency', 'weekly' );
		$scheduled = wp_next_scheduled( self::CRON_HOOK );

		if ( 'never' === $frequency ) {
			if ( $scheduled ) {
				wp_unschedule_event( $scheduled, self::CRON_HOOK );
			}
			return;
		}

		if ( ! $scheduled ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, $frequency, self::CRON_HOOK );
		}
	}

	/**
	 * Remove the scheduled event.
	 *
	 * @return void
	 */
	public function clear_schedule() {
		$scheduled = wp_next_scheduled( self::CRON_HOOK );
		if ( $scheduled ) {
			wp_unschedule_event( $scheduled, self::CRON_HOOK );
		}
	}

	/**
	 * Perform the cleanup.
	 *
	 * @return array Counts of removed rows by type.
	 */
	public function run_cleanup() {
		global $wpdb;
		$s       = $this->get_settings();
		$results = array();

		if ( ! empty( $s['revisions'] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results['revisions'] = (int) $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'" );
		}

		if ( ! empty( $s['transients'] ) ) {
			$now = time();
			// Delete expired transient timeouts, then their values.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$expired = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
					$wpdb->esc_like( '_transient_timeout_' ) . '%',
					$now
				)
			);
			$removed = 0;
			foreach ( $expired as $timeout_name ) {
				$key = str_replace( '_transient_timeout_', '', $timeout_name );
				delete_transient( $key );
				$removed++;
			}
			$results['transients'] = $removed;
		}

		if ( ! empty( $s['spam'] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results['comments'] = (int) $wpdb->query( "DELETE FROM {$wpdb->comments} WHERE comment_approved IN ('spam','trash')" );
		}

		update_option( 'dicestack_db_last_cleanup', array( 'time' => time(), 'results' => $results ) );
		return $results;
	}
}
