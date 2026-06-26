<?php
/**
 * Excerpt Control module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Customises automatic excerpt length and the "read more" string.
 */
final class Excerpt_Control extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'excerpt_control';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Excerpt control', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Set the automatic excerpt length and the read-more text.', 'dicestack' );
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
	public function settings_schema() {
		return array(
			array(
				'key'     => 'length',
				'label'   => __( 'Excerpt length (words)', 'dicestack' ),
				'type'    => 'number',
				'default' => 40,
				'min'     => 5,
				'max'     => 200,
				'step'    => 1,
			),
			array(
				'key'     => 'more',
				'label'   => __( 'Read-more text', 'dicestack' ),
				'type'    => 'text',
				'default' => '…',
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'excerpt_length', array( $this, 'length' ), 999 );
		add_filter( 'excerpt_more', array( $this, 'more' ), 999 );
	}

	/**
	 * Provide the configured length.
	 *
	 * @param int $length Default length.
	 * @return int
	 */
	public function length( $length ) {
		return (int) $this->get_setting( 'length', 40 );
	}

	/**
	 * Provide the configured read-more string.
	 *
	 * @param string $more Default string.
	 * @return string
	 */
	public function more( $more ) {
		return ' ' . esc_html( (string) $this->get_setting( 'more', '…' ) );
	}
}
