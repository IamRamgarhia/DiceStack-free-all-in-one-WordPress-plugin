<?php
/**
 * Activity Log module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Records key admin events (logins, content changes, plugin/theme changes, user
 * changes) into a capped log viewable under DiceStack. Replaces WP Activity Log
 * basics. Stored as a single capped option to avoid table migrations.
 */
final class Activity_Log extends Abstract_Module {

	/**
	 * Option storing the log entries.
	 */
	const OPTION = 'dicestack_activity_log';

	/**
	 * Maximum entries kept.
	 */
	const CAP = 300;

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'activity_log';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Activity log', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Track logins, content changes, and plugin/user activity in an audit trail.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'security';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'file-code';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium activity-log plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 55,
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
		add_action( 'wp_login', array( $this, 'on_login' ), 10, 2 );
		add_action( 'wp_login_failed', array( $this, 'on_login_failed' ) );
		add_action( 'transition_post_status', array( $this, 'on_post_status' ), 10, 3 );
		add_action( 'delete_post', array( $this, 'on_delete_post' ) );
		add_action( 'activated_plugin', array( $this, 'on_plugin_activated' ) );
		add_action( 'deactivated_plugin', array( $this, 'on_plugin_deactivated' ) );
		add_action( 'user_register', array( $this, 'on_user_register' ) );
		add_action( 'switch_theme', array( $this, 'on_switch_theme' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_page' ) );
		}
	}

	/**
	 * Append an entry to the log.
	 *
	 * @param string $action Short action label.
	 * @param string $detail Human-readable detail.
	 * @return void
	 */
	private function log( $action, $detail = '' ) {
		$user = wp_get_current_user();
		$log  = get_option( self::OPTION, array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}

		array_unshift(
			$log,
			array(
				'time'   => time(),
				'user'   => $user && $user->exists() ? $user->user_login : '—',
				'action' => $action,
				'detail' => $detail,
				'ip'     => $this->ip(),
			)
		);

		if ( count( $log ) > self::CAP ) {
			$log = array_slice( $log, 0, self::CAP );
		}
		update_option( self::OPTION, $log, false );
	}

	/**
	 * Best-effort client IP.
	 *
	 * @return string
	 */
	private function ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/* ----- Event handlers ------------------------------------------------- */

	/**
	 * Log a successful login.
	 *
	 * @param string   $login Username.
	 * @param \WP_User $user  User object.
	 * @return void
	 */
	public function on_login( $login, $user = null ) {
		$this->log( 'login', sprintf( /* translators: %s: username. */ __( 'User %s logged in', 'dicestack' ), $login ) );
	}

	/**
	 * Log a failed login.
	 *
	 * @param string $login Attempted username.
	 * @return void
	 */
	public function on_login_failed( $login ) {
		$this->log( 'login_failed', sprintf( /* translators: %s: username. */ __( 'Failed login for %s', 'dicestack' ), $login ) );
	}

	/**
	 * Log post publish/update transitions.
	 *
	 * @param string   $new New status.
	 * @param string   $old Old status.
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function on_post_status( $new, $old, $post ) {
		if ( wp_is_post_revision( $post ) || 'auto-draft' === $new ) {
			return;
		}
		if ( in_array( $post->post_type, array( 'dicestack_entry' ), true ) ) {
			return;
		}
		if ( 'publish' === $new && 'publish' !== $old ) {
			$this->log( 'post_published', sprintf( /* translators: 1: type, 2: title. */ __( 'Published %1$s: %2$s', 'dicestack' ), $post->post_type, get_the_title( $post ) ) );
		} elseif ( 'publish' === $new && 'publish' === $old ) {
			$this->log( 'post_updated', sprintf( /* translators: 1: type, 2: title. */ __( 'Updated %1$s: %2$s', 'dicestack' ), $post->post_type, get_the_title( $post ) ) );
		}
	}

	/**
	 * Log post deletion.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function on_delete_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || wp_is_post_revision( $post_id ) || 'auto-draft' === $post->post_status ) {
			return;
		}
		$this->log( 'post_deleted', sprintf( /* translators: %s: title. */ __( 'Deleted: %s', 'dicestack' ), get_the_title( $post ) ) );
	}

	/**
	 * Log plugin activation.
	 *
	 * @param string $plugin Plugin path.
	 * @return void
	 */
	public function on_plugin_activated( $plugin ) {
		$this->log( 'plugin_activated', sprintf( /* translators: %s: plugin. */ __( 'Activated plugin: %s', 'dicestack' ), $plugin ) );
	}

	/**
	 * Log plugin deactivation.
	 *
	 * @param string $plugin Plugin path.
	 * @return void
	 */
	public function on_plugin_deactivated( $plugin ) {
		$this->log( 'plugin_deactivated', sprintf( /* translators: %s: plugin. */ __( 'Deactivated plugin: %s', 'dicestack' ), $plugin ) );
	}

	/**
	 * Log new user registration.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function on_user_register( $user_id ) {
		$u = get_userdata( $user_id );
		$this->log( 'user_registered', sprintf( /* translators: %s: username. */ __( 'New user: %s', 'dicestack' ), $u ? $u->user_login : $user_id ) );
	}

	/**
	 * Log theme switch.
	 *
	 * @param string $name New theme name.
	 * @return void
	 */
	public function on_switch_theme( $name ) {
		$this->log( 'theme_switched', sprintf( /* translators: %s: theme. */ __( 'Switched theme to %s', 'dicestack' ), $name ) );
	}

	/* ----- Admin viewer --------------------------------------------------- */

	/**
	 * Register the log viewer submenu.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'dicestack',
			__( 'Activity log', 'dicestack' ),
			__( 'Activity log', 'dicestack' ),
			'manage_options',
			'dicestack-activity-log',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the log table.
	 *
	 * @return void
	 */
	public function render_page() {
		$log = get_option( self::OPTION, array() );
		$log = is_array( $log ) ? $log : array();
		echo '<div class="wrap"><h1>' . esc_html__( 'DiceStack activity log', 'dicestack' ) . '</h1>';

		if ( empty( $log ) ) {
			echo '<p>' . esc_html__( 'No activity recorded yet.', 'dicestack' ) . '</p></div>';
			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'When', 'dicestack' ) . '</th>';
		echo '<th>' . esc_html__( 'User', 'dicestack' ) . '</th>';
		echo '<th>' . esc_html__( 'Event', 'dicestack' ) . '</th>';
		echo '<th>' . esc_html__( 'IP', 'dicestack' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $log as $row ) {
			$when = isset( $row['time'] ) ? sprintf( /* translators: %s: time diff. */ __( '%s ago', 'dicestack' ), human_time_diff( (int) $row['time'], time() ) ) : '';
			echo '<tr>';
			echo '<td>' . esc_html( $when ) . '</td>';
			echo '<td>' . esc_html( isset( $row['user'] ) ? $row['user'] : '' ) . '</td>';
			echo '<td>' . esc_html( isset( $row['detail'] ) ? $row['detail'] : '' ) . '</td>';
			echo '<td>' . esc_html( isset( $row['ip'] ) ? $row['ip'] : '' ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}
}
