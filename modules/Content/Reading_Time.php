<?php
/**
 * Reading Time module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Shows an estimated reading time at the top of single posts.
 */
final class Reading_Time extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'reading_time';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Reading time', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Display an estimated reading time at the top of posts.', 'dicestack' );
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
				'key'     => 'wpm',
				'label'   => __( 'Average reading speed (words per minute)', 'dicestack' ),
				'type'    => 'number',
				'default' => 200,
				'min'     => 100,
				'max'     => 400,
				'step'    => 10,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'the_content', array( $this, 'prepend' ), 5 );
	}

	/**
	 * Prepend the reading-time line on single posts.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function prepend( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$words   = str_word_count( wp_strip_all_tags( $content ) );
		$wpm     = max( 100, (int) $this->get_setting( 'wpm', 200 ) );
		$minutes = max( 1, (int) ceil( $words / $wpm ) );

		$label = sprintf(
			/* translators: %d: minutes. */
			_n( '%d minute read', '%d minute read', $minutes, 'dicestack' ),
			$minutes
		);

		$badge = '<p class="dicestack-reading-time" style="color:#6b7280;font-size:13px;margin:0 0 16px;">' . esc_html( $label ) . '</p>';
		return $badge . $content;
	}
}
