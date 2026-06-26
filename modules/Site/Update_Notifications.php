<?php
/**
 * Update Notifications module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Site;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Emails the administrator a daily digest when core, plugin, or theme updates
 * are available — so nothing sits unpatched unnoticed.
 */
final class Update_Notifications extends Abstract_Module {

	/**
	 * Cron hook name.
	 */
	const CRON_HOOK = 'dicestack_update_check';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'update_notifications';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Update notifications', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Get an email when core, plugin, or theme updates become available.', 'dicestack' );
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
				'key'     => 'recipient',
				'label'   => __( 'Notify this email', 'dicestack' ),
				'type'    => 'text',
				'default' => get_option( 'admin_email' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( self::CRON_HOOK, array( $this, 'check' ) );
		add_action( 'init', array( $this, 'ensure_schedule' ) );
		add_action( 'dicestack_module_disabled_' . $this->id(), array( $this, 'clear_schedule' ) );
	}

	/**
	 * Schedule the daily check if not already scheduled.
	 *
	 * @return void
	 */
	public function ensure_schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Remove the scheduled event.
	 *
	 * @return void
	 */
	public function clear_schedule() {
		$ts = wp_next_scheduled( self::CRON_HOOK );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::CRON_HOOK );
		}
	}

	/**
	 * Check for available updates and email if any.
	 *
	 * @return void
	 */
	public function check() {
		if ( ! function_exists( 'wp_get_update_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}
		$data  = wp_get_update_data();
		$count = isset( $data['counts']['total'] ) ? (int) $data['counts']['total'] : 0;
		if ( $count < 1 ) {
			return;
		}

		$counts  = $data['counts'];
		$plugins = isset( $counts['plugins'] ) ? (int) $counts['plugins'] : 0;
		$themes  = isset( $counts['themes'] ) ? (int) $counts['themes'] : 0;
		$wpcore  = isset( $counts['wordpress'] ) ? (int) $counts['wordpress'] : 0;

		$recipient = sanitize_email( (string) $this->get_setting( 'recipient', get_option( 'admin_email' ) ) );
		$recipient = is_email( $recipient ) ? $recipient : get_option( 'admin_email' );

		$body  = sprintf(
			/* translators: %s: site name. */
			__( "Updates are available on %s:", 'dicestack' ),
			get_bloginfo( 'name' )
		) . "\n\n";
		/* translators: %d: number of core updates. */
		$body .= sprintf( __( '- WordPress core: %d', 'dicestack' ), $wpcore ) . "\n";
		/* translators: %d: number of plugin updates. */
		$body .= sprintf( __( '- Plugins: %d', 'dicestack' ), $plugins ) . "\n";
		/* translators: %d: number of theme updates. */
		$body .= sprintf( __( '- Themes: %d', 'dicestack' ), $themes ) . "\n\n";
		$body .= admin_url( 'update-core.php' ) . "\n";

		wp_mail(
			$recipient,
			sprintf(
				/* translators: 1: site name, 2: number of updates. */
				__( '[%1$s] %2$d update(s) available', 'dicestack' ),
				get_bloginfo( 'name' ),
				$count
			),
			$body
		);
	}
}
