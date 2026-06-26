<?php
/**
 * Footer Copyright module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Provides [dicestack_year] and [dicestack_copyright] shortcodes for an always-current
 * copyright line in the footer.
 */
final class Footer_Copyright extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'footer_copyright';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Auto copyright', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Use [dicestack_copyright] or [dicestack_year] for an always-current footer credit.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'content';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'forms';
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
	public function settings_schema() {
		return array(
			array(
				'key'     => 'start_year',
				'label'   => __( 'Start year (optional)', 'dicestack' ),
				'type'    => 'number',
				'default' => 0,
				'min'     => 0,
				'max'     => 2100,
				'step'    => 1,
				'help'    => __( 'If set and earlier than this year, shows a range like 2018–2026.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_shortcode( 'dicestack_year', array( $this, 'year' ) );
		add_shortcode( 'dicestack_copyright', array( $this, 'copyright' ) );
	}

	/**
	 * Current year, or a start–current range.
	 *
	 * @return string
	 */
	public function year() {
		$now   = (int) gmdate( 'Y' );
		$start = (int) $this->get_setting( 'start_year', 0 );
		if ( $start > 0 && $start < $now ) {
			return $start . '&ndash;' . $now;
		}
		return (string) $now;
	}

	/**
	 * Full copyright line.
	 *
	 * @return string
	 */
	public function copyright() {
		return '&copy; ' . $this->year() . ' ' . esc_html( get_bloginfo( 'name' ) );
	}
}
