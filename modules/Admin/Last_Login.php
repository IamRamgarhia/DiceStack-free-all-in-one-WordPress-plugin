<?php
/**
 * Last Login tracker module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Records each user's last login time and shows it as a column on the Users
 * screen — useful for spotting dormant or suspicious accounts.
 */
final class Last_Login extends Abstract_Module {

	/**
	 * Meta key for the last-login timestamp.
	 */
	const META = 'dicestack_last_login';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'last_login';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Last login tracker', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Record when each user last logged in and show it on the Users screen.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'admin';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'lock';
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
		add_action( 'wp_login', array( $this, 'record' ), 10, 2 );

		if ( is_admin() ) {
			add_filter( 'manage_users_columns', array( $this, 'add_column' ) );
			add_filter( 'manage_users_custom_column', array( $this, 'render_column' ), 10, 3 );
		}
	}

	/**
	 * Store the login timestamp.
	 *
	 * @param string   $user_login Username.
	 * @param \WP_User $user       User object.
	 * @return void
	 */
	public function record( $user_login, $user = null ) {
		if ( $user instanceof \WP_User ) {
			update_user_meta( $user->ID, self::META, time() );
		}
	}

	/**
	 * Add the Last login column.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function add_column( $columns ) {
		$columns['dicestack_last_login'] = __( 'Last login', 'dicestack' );
		return $columns;
	}

	/**
	 * Render the Last login column.
	 *
	 * @param string $output      Column output.
	 * @param string $column_name Column key.
	 * @param int    $user_id     User ID.
	 * @return string
	 */
	public function render_column( $output, $column_name, $user_id ) {
		if ( 'dicestack_last_login' !== $column_name ) {
			return $output;
		}
		$ts = (int) get_user_meta( $user_id, self::META, true );
		if ( ! $ts ) {
			return esc_html__( 'Never', 'dicestack' );
		}
		return esc_html(
			sprintf(
				/* translators: %s: human-readable time difference. */
				__( '%s ago', 'dicestack' ),
				human_time_diff( $ts, time() )
			)
		);
	}
}
