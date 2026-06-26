<?php
/**
 * Noindex Controls module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds noindex to low-value archive types (search, author, date, tag) to keep
 * them out of search results and concentrate ranking signals.
 */
final class Noindex_Controls extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'noindex_controls';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Noindex controls', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Keep thin archive pages (search, author, date) out of search engines.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'seo';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'search';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 18,
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
				'key'     => 'search',
				'label'   => __( 'Noindex search results', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'author',
				'label'   => __( 'Noindex author archives', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'date',
				'label'   => __( 'Noindex date archives', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'tag',
				'label'   => __( 'Noindex tag archives', 'dicestack' ),
				'type'    => 'toggle',
				'default' => false,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'wp_robots', array( $this, 'robots' ) );
	}

	/**
	 * Apply noindex to the configured contexts (WP 5.7+ wp_robots filter).
	 *
	 * @param array $robots Robots directives.
	 * @return array
	 */
	public function robots( $robots ) {
		$noindex = false;

		if ( ! empty( $this->get_setting( 'search', true ) ) && is_search() ) {
			$noindex = true;
		} elseif ( ! empty( $this->get_setting( 'author', true ) ) && is_author() ) {
			$noindex = true;
		} elseif ( ! empty( $this->get_setting( 'date', true ) ) && is_date() ) {
			$noindex = true;
		} elseif ( ! empty( $this->get_setting( 'tag', false ) ) && is_tag() ) {
			$noindex = true;
		}

		if ( $noindex ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}

		return $robots;
	}
}
