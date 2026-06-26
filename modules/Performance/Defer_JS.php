<?php
/**
 * Defer JavaScript module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the defer attribute to front-end scripts so they don't block rendering.
 * jQuery is excluded by default to avoid breaking inline dependencies; you can
 * add more exclusions if a script misbehaves.
 */
final class Defer_JS extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'defer_js';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Defer JavaScript', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add defer to front-end scripts to stop them blocking the first paint.', 'dicestack' );
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
	public function replaces() {
		return 'premium caching plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 18,
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
				'key'     => 'exclude',
				'label'   => __( 'Script handles to NOT defer', 'dicestack' ),
				'type'    => 'textarea',
				'default' => "jquery-core\njquery-migrate",
				'help'    => __( 'One script handle per line. Defaults keep jQuery non-deferred for safety.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		if ( is_admin() ) {
			return;
		}
		add_filter( 'script_loader_tag', array( $this, 'defer' ), 10, 2 );
	}

	/**
	 * Add defer to eligible script tags.
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @return string
	 */
	public function defer( $tag, $handle ) {
		if ( is_admin() || strpos( $tag, ' defer' ) !== false || strpos( $tag, ' async' ) !== false ) {
			return $tag;
		}

		$exclude = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $this->get_setting( 'exclude', '' ) ) as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) {
				$exclude[] = $line;
			}
		}
		if ( in_array( $handle, $exclude, true ) ) {
			return $tag;
		}

		return str_replace( ' src=', ' defer src=', $tag );
	}
}
