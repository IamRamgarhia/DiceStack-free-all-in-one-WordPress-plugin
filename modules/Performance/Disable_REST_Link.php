<?php
/**
 * Disable REST Link module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the REST API discovery link tag and header from the front end. The
 * REST API keeps working; only the advertising of it is removed.
 */
final class Disable_REST_Link extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'disable_rest_link';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Remove REST API link tag', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Clean the REST API discovery link and header from the page head.', 'dicestack' );
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
	public function performance_profile() {
		return array(
			'php_memory_kb' => 10,
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
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );
		remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
	}
}
