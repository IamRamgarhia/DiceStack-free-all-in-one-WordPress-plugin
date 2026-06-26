<?php
/**
 * Remove Query Strings module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Strips ?ver= query strings from static CSS/JS so some proxies and CDNs cache
 * them more reliably.
 */
final class Remove_Query_Strings extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'remove_query_strings';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Remove asset query strings', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Strip ?ver= from CSS/JS URLs so caches and CDNs store them reliably.', 'dicestack' );
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
			'php_memory_kb' => 14,
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
		if ( is_admin() ) {
			return;
		}
		add_filter( 'style_loader_src', array( $this, 'strip' ), 15 );
		add_filter( 'script_loader_src', array( $this, 'strip' ), 15 );
	}

	/**
	 * Remove the ver query arg.
	 *
	 * @param string $src Asset URL.
	 * @return string
	 */
	public function strip( $src ) {
		if ( $src && strpos( $src, 'ver=' ) !== false ) {
			$src = remove_query_arg( 'ver', $src );
		}
		return $src;
	}
}
