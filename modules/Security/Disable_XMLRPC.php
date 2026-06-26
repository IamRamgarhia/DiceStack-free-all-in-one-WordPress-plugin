<?php
/**
 * Disable XML-RPC module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Disables the XML-RPC endpoint, a frequent target for brute-force
 * amplification and pingback DDoS attacks.
 */
final class Disable_XMLRPC extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'disable_xmlrpc';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Disable XML-RPC', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Turn off the XML-RPC API to close a common brute-force and DDoS vector.', 'dicestack' );
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
		return 'plug-off';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 20,
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
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'xmlrpc_methods', '__return_empty_array' );
		// Remove the RSD/pingback discovery headers and link tag.
		add_filter( 'wp_headers', array( $this, 'remove_pingback_header' ) );
		remove_action( 'wp_head', 'rsd_link' );
	}

	/**
	 * Remove the X-Pingback header.
	 *
	 * @param array $headers Response headers.
	 * @return array
	 */
	public function remove_pingback_header( $headers ) {
		if ( isset( $headers['X-Pingback'] ) ) {
			unset( $headers['X-Pingback'] );
		}
		return $headers;
	}
}
