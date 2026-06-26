<?php
/**
 * Block wp-admin for Subscribers module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Stops low-privilege users (subscribers/customers) from reaching the wp-admin
 * dashboard, redirecting them to the home page instead. AJAX is left working.
 */
final class Block_Admin_Subscribers extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'block_admin_subscribers';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Restrict dashboard access', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Keep subscribers and customers out of wp-admin by redirecting them to the site.', 'dicestack' );
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
		return 'lock';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 12,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 0,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'maybe_block' ) );
	}

	/**
	 * Redirect non-privileged users away from wp-admin.
	 *
	 * @return void
	 */
	public function maybe_block() {
		if ( wp_doing_ajax() ) {
			return;
		}
		// Allow anyone who can edit posts (contributor and up) to stay.
		if ( current_user_can( 'edit_posts' ) ) {
			return;
		}
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
}
