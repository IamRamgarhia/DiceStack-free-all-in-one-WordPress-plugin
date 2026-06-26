<?php
/**
 * Disable Self Pingbacks module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Stops WordPress from pinging your own site when you link between your posts,
 * avoiding useless self-pingback comments and extra requests on publish.
 */
final class Disable_Self_Pingbacks extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'disable_self_pingbacks';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Disable self-pingbacks', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Stop WordPress pinging your own site when you link between your posts.', 'dicestack' );
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
		add_action( 'pre_ping', array( $this, 'remove_self' ) );
	}

	/**
	 * Remove internal links from the list of URLs to ping.
	 *
	 * @param array $links Links to ping (passed by reference).
	 * @return void
	 */
	public function remove_self( &$links ) {
		$home = home_url();
		foreach ( $links as $key => $link ) {
			if ( 0 === strpos( $link, $home ) ) {
				unset( $links[ $key ] );
			}
		}
	}
}
