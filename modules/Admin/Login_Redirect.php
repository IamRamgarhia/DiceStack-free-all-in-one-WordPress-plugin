<?php
/**
 * Login Redirect module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Sends non-admin users to a chosen URL after login instead of wp-admin —
 * useful for membership and customer-facing sites.
 */
final class Login_Redirect extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'login_redirect';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Login redirect', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Redirect non-admin users to a chosen page after they log in.', 'dicestack' );
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
		return 'arrow-back-up';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium login-redirect plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 14,
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
				'key'     => 'url',
				'label'   => __( 'Redirect non-admins to', 'dicestack' ),
				'type'    => 'url',
				'default' => home_url( '/' ),
				'help'    => __( 'Administrators always go to the dashboard.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'login_redirect', array( $this, 'redirect' ), 10, 3 );
	}

	/**
	 * Decide the post-login redirect.
	 *
	 * @param string           $redirect_to Default redirect.
	 * @param string           $requested   Requested redirect.
	 * @param \WP_User|\WP_Error $user      User or error.
	 * @return string
	 */
	public function redirect( $redirect_to, $requested, $user ) {
		if ( $user instanceof \WP_User && ! user_can( $user, 'manage_options' ) ) {
			$url = esc_url_raw( (string) $this->get_setting( 'url', home_url( '/' ) ) );
			if ( $url ) {
				return $url;
			}
		}
		return $redirect_to;
	}
}
