<?php
/**
 * Fix Missed Schedule module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Site;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Publishes scheduled posts that WordPress missed (a common WP-Cron problem that
 * leaves posts stuck on "Missed schedule"). Checks at most once every 10 minutes.
 */
final class Fix_Missed_Schedule extends Abstract_Module {

	/**
	 * Throttle transient.
	 */
	const THROTTLE = 'dicestack_missed_schedule_check';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'fix_missed_schedule';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Fix missed schedule', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Automatically publish scheduled posts that WordPress failed to publish on time.', 'dicestack' );
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
		return 'server';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 20,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 1,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_loaded', array( $this, 'maybe_run' ) );
	}

	/**
	 * Run the check, throttled to once per 10 minutes.
	 *
	 * @return void
	 */
	public function maybe_run() {
		if ( get_transient( self::THROTTLE ) ) {
			return;
		}
		set_transient( self::THROTTLE, 1, 10 * MINUTE_IN_SECONDS );

		global $wpdb;
		$now = current_time( 'mysql', false );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'future' AND post_date_gmt > '0000-00-00 00:00:00' AND post_date <= %s LIMIT 20",
				$now
			)
		);

		foreach ( $ids as $id ) {
			wp_publish_post( (int) $id );
		}
	}
}
