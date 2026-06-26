<?php
/**
 * Frontend Error Monitor module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Site;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Catches fatal PHP errors (the usual cause of a blank "white screen" or a site
 * stuck loading), logs them with the likely culprit plugin/theme and a
 * suggested fix, and can email the admin. Helps diagnose front-end breakages.
 */
final class Error_Monitor extends Abstract_Module {

	/**
	 * Option storing recent caught errors.
	 */
	const OPTION = 'dicestack_error_log';

	/**
	 * Max stored errors.
	 */
	const CAP = 50;

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'error_monitor';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Error monitor', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Capture fatal PHP errors that break the site and suggest the likely cause and fix.', 'dicestack' );
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
		return 'shield';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 25,
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
				'key'     => 'email_admin',
				'label'   => __( 'Email the admin when a fatal error is caught', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		register_shutdown_function( array( $this, 'on_shutdown' ) );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_page' ) );
		}
	}

	/**
	 * On shutdown, record any fatal error.
	 *
	 * @return void
	 */
	public function on_shutdown() {
		$err = error_get_last();
		if ( ! $err || ! in_array( $err['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR ), true ) ) {
			return;
		}

		$culprit = $this->guess_culprit( isset( $err['file'] ) ? $err['file'] : '' );
		$entry   = array(
			'time'    => time(),
			'message' => isset( $err['message'] ) ? substr( $err['message'], 0, 500 ) : '',
			'file'    => isset( $err['file'] ) ? $err['file'] : '',
			'line'    => isset( $err['line'] ) ? (int) $err['line'] : 0,
			'culprit' => $culprit,
		);

		$log = get_option( self::OPTION, array() );
		$log = is_array( $log ) ? $log : array();
		array_unshift( $log, $entry );
		$log = array_slice( $log, 0, self::CAP );
		update_option( self::OPTION, $log, false );

		if ( ! empty( $this->get_setting( 'email_admin', true ) ) ) {
			$this->maybe_email( $entry );
		}
	}

	/**
	 * Identify the plugin or theme a file path belongs to.
	 *
	 * @param string $file File path.
	 * @return string
	 */
	private function guess_culprit( $file ) {
		$file = wp_normalize_path( (string) $file );
		if ( preg_match( '#/plugins/([^/]+)/#', $file, $m ) ) {
			/* translators: %s: plugin folder. */
			return sprintf( __( 'Plugin: %s', 'dicestack' ), $m[1] );
		}
		if ( preg_match( '#/themes/([^/]+)/#', $file, $m ) ) {
			/* translators: %s: theme folder. */
			return sprintf( __( 'Theme: %s', 'dicestack' ), $m[1] );
		}
		if ( false !== strpos( $file, '/wp-includes/' ) || false !== strpos( $file, '/wp-admin/' ) ) {
			return __( 'WordPress core', 'dicestack' );
		}
		return __( 'Unknown', 'dicestack' );
	}

	/**
	 * Email the admin at most once per hour.
	 *
	 * @param array $entry Error entry.
	 * @return void
	 */
	private function maybe_email( $entry ) {
		if ( get_transient( 'dicestack_error_emailed' ) ) {
			return;
		}
		set_transient( 'dicestack_error_emailed', 1, HOUR_IN_SECONDS );

		$body  = __( 'A fatal error was detected on your site:', 'dicestack' ) . "\n\n";
		$body .= $entry['message'] . "\n\n";
		$body .= __( 'Likely cause', 'dicestack' ) . ': ' . $entry['culprit'] . "\n";
		$body .= $entry['file'] . ':' . $entry['line'] . "\n\n";
		$body .= __( 'Suggested fix: deactivate the plugin/theme above, or use Recovery Mode.', 'dicestack' );

		wp_mail( get_option( 'admin_email' ), '[' . get_bloginfo( 'name' ) . '] ' . __( 'Fatal error detected', 'dicestack' ), $body );
	}

	/**
	 * Register the error log page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'dicestack',
			__( 'Error monitor', 'dicestack' ),
			__( 'Error monitor', 'dicestack' ),
			'manage_options',
			'dicestack-errors',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the error log.
	 *
	 * @return void
	 */
	public function render_page() {
		$log = get_option( self::OPTION, array() );
		$log = is_array( $log ) ? $log : array();
		echo '<div class="wrap"><h1>' . esc_html__( 'Error monitor', 'dicestack' ) . '</h1>';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.
		if ( isset( $_GET['settings-saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'dicestack' ) . '</p></div>';
		}
		echo \DiceStack\Admin\Settings_Renderer::page_form( $this ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped internally.
		if ( empty( $log ) ) {
			echo '<p>' . esc_html__( 'No fatal errors recorded. Your site is healthy.', 'dicestack' ) . '</p></div>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'When', 'dicestack' ) . '</th><th>' . esc_html__( 'Likely cause', 'dicestack' ) . '</th><th>' . esc_html__( 'Message', 'dicestack' ) . '</th></tr></thead><tbody>';
		foreach ( $log as $e ) {
			$when = isset( $e['time'] ) ? sprintf( /* translators: %s: time diff. */ __( '%s ago', 'dicestack' ), human_time_diff( (int) $e['time'], time() ) ) : '';
			echo '<tr><td>' . esc_html( $when ) . '</td><td><strong>' . esc_html( isset( $e['culprit'] ) ? $e['culprit'] : '' ) . '</strong></td><td><code style="font-size:11px;">' . esc_html( isset( $e['message'] ) ? $e['message'] : '' ) . '</code></td></tr>';
		}
		echo '</tbody></table>';
		echo '<p>' . esc_html__( 'Tip: if the front end is broken, the "Likely cause" above is usually the plugin or theme to deactivate.', 'dicestack' ) . '</p></div>';
	}
}
