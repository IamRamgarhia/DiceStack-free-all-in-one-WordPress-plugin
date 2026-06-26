<?php
/**
 * Updated Date module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Shows a "Last updated" date on posts that were modified well after publishing
 * — a trust and freshness signal for readers and search engines.
 */
final class Updated_Date extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'updated_date';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Last updated date', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Show a "last updated" date on posts that were revised after publishing.', 'dicestack' );
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
		return 'search';
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
	public function settings_schema() {
		return array(
			array(
				'key'     => 'min_days',
				'label'   => __( 'Only show if updated at least N days after publishing', 'dicestack' ),
				'type'    => 'number',
				'default' => 7,
				'min'     => 0,
				'max'     => 365,
				'step'    => 1,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'the_content', array( $this, 'prepend' ), 6 );
	}

	/**
	 * Prepend the updated date when appropriate.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function prepend( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$published = (int) get_post_time( 'U', true );
		$modified  = (int) get_post_modified_time( 'U', true );
		$min       = (int) $this->get_setting( 'min_days', 7 ) * DAY_IN_SECONDS;

		if ( ( $modified - $published ) < $min ) {
			return $content;
		}

		$label = sprintf(
			/* translators: %s: formatted date. */
			__( 'Last updated on %s', 'dicestack' ),
			get_the_modified_date()
		);
		return '<p class="dicestack-updated" style="color:#6b7280;font-size:13px;margin:0 0 16px;">' . esc_html( $label ) . '</p>' . $content;
	}
}
