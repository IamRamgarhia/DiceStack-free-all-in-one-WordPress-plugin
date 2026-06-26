<?php
/**
 * Monthly Report module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Site;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Emails a branded monthly site report (content, comments, updates, backups,
 * security events) to the owner or client — the heart of agency mode.
 */
final class Monthly_Report extends Abstract_Module {

	/**
	 * Cron hook.
	 */
	const CRON_HOOK = 'dicestack_monthly_report';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'monthly_report';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Monthly report', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Email a branded monthly summary of activity, updates, and backups to clients.', 'dicestack' );
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
		return 'chart-bar';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 30,
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
				'label'   => __( 'Send report to', 'dicestack' ),
				'type'    => 'text',
				'default' => get_option( 'admin_email' ),
				'help'    => __( 'Comma-separate multiple addresses.', 'dicestack' ),
			),
			array(
				'key'     => 'company',
				'label'   => __( 'Report footer / company', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'Prepared by Dice Codes', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( self::CRON_HOOK, array( $this, 'send' ) );
		add_action( 'init', array( $this, 'ensure_schedule' ) );
		add_action( 'dicestack_module_disabled_' . $this->id(), array( $this, 'clear_schedule' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_page' ) );
			add_action( 'admin_post_dicestack_send_report', array( $this, 'handle_send_now' ) );
		}
	}

	/**
	 * Schedule the report. WordPress lacks a native monthly cron, so we use a
	 * weekly tick and only send on the 1st of the month.
	 *
	 * @return void
	 */
	public function ensure_schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Remove the cron event.
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
	 * Cron callback — only sends on the 1st.
	 *
	 * @return void
	 */
	public function send() {
		if ( '01' !== gmdate( 'd' ) ) {
			return;
		}
		$this->dispatch();
	}

	/**
	 * Gather the report data.
	 *
	 * @return array
	 */
	private function gather() {
		$since = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );

		$posts = get_posts(
			array(
				'post_type'   => 'post',
				'date_query'  => array( array( 'after' => '30 days ago' ) ),
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);

		$comments = get_comments(
			array(
				'count'      => true,
				'date_query' => array( array( 'after' => '30 days ago' ) ),
			)
		);

		$last_backup = (int) get_option( 'dicestack_last_backup', 0 );
		$errors      = get_option( 'dicestack_error_log', array() );
		$updates     = 0;
		if ( function_exists( 'wp_get_update_data' ) ) {
			$ud      = wp_get_update_data();
			$updates = isset( $ud['counts']['total'] ) ? (int) $ud['counts']['total'] : 0;
		}

		return array(
			'posts'       => is_array( $posts ) ? count( $posts ) : 0,
			'comments'    => (int) $comments,
			'last_backup' => $last_backup ? gmdate( 'Y-m-d', $last_backup ) : __( 'never', 'dicestack' ),
			'errors'      => is_array( $errors ) ? count( $errors ) : 0,
			'updates'     => $updates,
		);
	}

	/**
	 * Build the HTML report body.
	 *
	 * @return string
	 */
	private function build_html() {
		$d    = $this->gather();
		$name = get_bloginfo( 'name' );
		$rows = array(
			__( 'New posts published', 'dicestack' )       => $d['posts'],
			__( 'New comments', 'dicestack' )              => $d['comments'],
			__( 'Updates available now', 'dicestack' )     => $d['updates'],
			__( 'Fatal errors logged', 'dicestack' )       => $d['errors'],
			__( 'Last backup', 'dicestack' )               => $d['last_backup'],
		);

		$html  = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;">';
		$html .= '<div style="background:#1b2a4a;color:#fff;padding:20px;border-radius:8px 8px 0 0;">';
		$html .= '<h1 style="margin:0;font-size:20px;">' . esc_html( $name ) . '</h1>';
		$html .= '<p style="margin:4px 0 0;opacity:.8;">' . esc_html( sprintf( /* translators: %s: month. */ __( 'Monthly report — %s', 'dicestack' ), gmdate( 'F Y' ) ) ) . '</p></div>';
		$html .= '<table style="width:100%;border-collapse:collapse;">';
		foreach ( $rows as $label => $value ) {
			$html .= '<tr><td style="padding:12px 20px;border-bottom:1px solid #eee;color:#555;">' . esc_html( $label ) . '</td><td style="padding:12px 20px;border-bottom:1px solid #eee;text-align:right;font-weight:bold;">' . esc_html( (string) $value ) . '</td></tr>';
		}
		$html .= '</table>';
		$html .= '<p style="padding:16px 20px;color:#9aa3af;font-size:12px;">' . esc_html( (string) $this->get_setting( 'company', '' ) ) . ' · ' . esc_url( home_url() ) . '</p>';
		$html .= '</div>';
		return $html;
	}

	/**
	 * Send the report email.
	 *
	 * @return bool
	 */
	private function dispatch() {
		$to = array_filter( array_map( 'trim', explode( ',', (string) $this->get_setting( 'recipient', get_option( 'admin_email' ) ) ) ) );
		if ( empty( $to ) ) {
			return false;
		}
		$subject = sprintf( /* translators: 1: site, 2: month. */ __( '%1$s — Monthly report (%2$s)', 'dicestack' ), get_bloginfo( 'name' ), gmdate( 'F Y' ) );
		return wp_mail( $to, $subject, $this->build_html(), array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	/**
	 * Register the page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'dicestack',
			__( 'Monthly report', 'dicestack' ),
			__( 'Monthly report', 'dicestack' ),
			'manage_options',
			'dicestack-report',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Send a report now (preview/test).
	 *
	 * @return void
	 */
	public function handle_send_now() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dicestack_send_report' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}
		$ok = $this->dispatch();
		wp_safe_redirect( admin_url( 'admin.php?page=dicestack-report&sent=' . ( $ok ? '1' : '0' ) ) );
		exit;
	}

	/**
	 * Render the report preview page.
	 *
	 * @return void
	 */
	public function render_page() {
		$sent = isset( $_GET['sent'] ) ? sanitize_text_field( wp_unslash( $_GET['sent'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="wrap"><h1>' . esc_html__( 'Monthly report', 'dicestack' ) . '</h1>';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.
		if ( isset( $_GET['settings-saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'dicestack' ) . '</p></div>';
		}
		echo '<h2>' . esc_html__( 'Settings', 'dicestack' ) . '</h2>';
		echo \DiceStack\Admin\Settings_Renderer::page_form( $this ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped internally.
		if ( '1' === $sent ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Report sent.', 'dicestack' ) . '</p></div>';
		} elseif ( '0' === $sent ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Could not send. Check the recipient and your mail setup (SMTP module helps).', 'dicestack' ) . '</p></div>';
		}
		echo '<p>' . esc_html__( 'Reports are emailed automatically on the 1st of each month. Preview below:', 'dicestack' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-bottom:20px;">';
		wp_nonce_field( 'dicestack_send_report' );
		echo '<input type="hidden" name="action" value="dicestack_send_report" />';
		echo '<button type="submit" class="button button-primary">' . esc_html__( 'Send report now', 'dicestack' ) . '</button>';
		echo '</form>';
		echo '<div style="border:1px solid #e4e7ec;border-radius:8px;overflow:hidden;max-width:620px;">' . $this->build_html() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built internally + escaped.
		echo '</div>';
	}
}
