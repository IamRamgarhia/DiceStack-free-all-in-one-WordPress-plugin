<?php
/**
 * Comment Length Limit module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Enforces minimum and maximum comment lengths to cut low-effort spam and
 * overly long pastes.
 */
final class Comment_Length_Limit extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'comment_length_limit';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Comment length limits', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Set minimum and maximum comment lengths to reduce spam and noise.', 'dicestack' );
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
		return 'message-off';
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
				'key'     => 'min',
				'label'   => __( 'Minimum characters', 'dicestack' ),
				'type'    => 'number',
				'default' => 5,
				'min'     => 0,
				'max'     => 500,
				'step'    => 1,
			),
			array(
				'key'     => 'max',
				'label'   => __( 'Maximum characters', 'dicestack' ),
				'type'    => 'number',
				'default' => 5000,
				'min'     => 100,
				'max'     => 50000,
				'step'    => 100,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'preprocess_comment', array( $this, 'check' ) );
	}

	/**
	 * Validate the comment length.
	 *
	 * @param array $commentdata Comment data.
	 * @return array
	 */
	public function check( $commentdata ) {
		$len = function_exists( 'mb_strlen' ) ? mb_strlen( $commentdata['comment_content'] ) : strlen( $commentdata['comment_content'] );
		$min = (int) $this->get_setting( 'min', 5 );
		$max = (int) $this->get_setting( 'max', 5000 );

		if ( $len < $min ) {
			wp_die(
				esc_html( sprintf( /* translators: %d: min characters. */ __( 'Your comment is too short (minimum %d characters).', 'dicestack' ), $min ) ),
				esc_html__( 'Comment too short', 'dicestack' ),
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}
		if ( $len > $max ) {
			wp_die(
				esc_html( sprintf( /* translators: %d: max characters. */ __( 'Your comment is too long (maximum %d characters).', 'dicestack' ), $max ) ),
				esc_html__( 'Comment too long', 'dicestack' ),
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}
		return $commentdata;
	}
}
