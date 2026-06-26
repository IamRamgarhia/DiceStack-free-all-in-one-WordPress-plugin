<?php
/**
 * Comment Link Limit module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Rejects comments that contain more than a set number of links — the single
 * strongest signal of comment spam.
 */
final class Comment_Link_Limit extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'comment_link_limit';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Comment link limit', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Block comments that contain too many links — the top spam signal.', 'dicestack' );
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
		return 'message-off';
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
				'key'     => 'max_links',
				'label'   => __( 'Maximum links allowed in a comment', 'dicestack' ),
				'type'    => 'number',
				'default' => 2,
				'min'     => 0,
				'max'     => 20,
				'step'    => 1,
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
	 * Block over-linked comments.
	 *
	 * @param array $commentdata Comment data.
	 * @return array
	 */
	public function check( $commentdata ) {
		$max     = (int) $this->get_setting( 'max_links', 2 );
		$content = isset( $commentdata['comment_content'] ) ? $commentdata['comment_content'] : '';
		$links   = preg_match_all( '#https?://#i', $content, $m );

		if ( $links > $max ) {
			wp_die(
				esc_html__( 'Your comment contains too many links and was blocked.', 'dicestack' ),
				esc_html__( 'Comment blocked', 'dicestack' ),
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}
		return $commentdata;
	}
}
