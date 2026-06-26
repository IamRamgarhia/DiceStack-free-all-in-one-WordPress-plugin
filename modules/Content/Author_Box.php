<?php
/**
 * Author Box module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Shows an author bio box (avatar, name, description, website) below single
 * posts. Replaces Simple Author Box.
 */
final class Author_Box extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'author_box';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Author box', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Display an author bio with avatar and website below each post.', 'dicestack' );
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
		return 'user';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'author-box plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 22,
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
				'key'     => 'position',
				'label'   => __( 'Where to show', 'dicestack' ),
				'type'    => 'select',
				'default' => 'after',
				'options' => array(
					'after'  => __( 'After the post content', 'dicestack' ),
					'before' => __( 'Before the post content', 'dicestack' ),
					'manual' => __( 'Only where I place [dicestack_author_box]', 'dicestack' ),
				),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'the_content', array( $this, 'append' ), 45 );
		add_shortcode( 'dicestack_author_box', array( $this, 'shortcode' ) );
	}

	/**
	 * Shortcode handler.
	 *
	 * @return string
	 */
	public function shortcode() {
		return $this->build();
	}

	/**
	 * Auto-place the author box (unless set to manual).
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function append( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$pos = $this->get_setting( 'position', 'after' );
		if ( 'manual' === $pos ) {
			return $content;
		}
		$box = $this->build();
		if ( '' === $box ) {
			return $content;
		}
		return ( 'before' === $pos ) ? $box . $content : $content . $box;
	}

	/**
	 * Build the author box HTML.
	 *
	 * @return string
	 */
	public function build() {
		if ( ! is_singular( 'post' ) ) {
			return '';
		}
		$author_id = get_the_author_meta( 'ID' );
		$name      = get_the_author_meta( 'display_name', $author_id );
		$bio       = get_the_author_meta( 'description', $author_id );
		if ( '' === trim( (string) $bio ) ) {
			return '';
		}
		$url    = get_the_author_meta( 'user_url', $author_id );
		$avatar = get_avatar( $author_id, 72, '', $name, array( 'style' => 'border-radius:50%;' ) );

		$box  = '<div class="dicestack-author-box" style="display:flex;gap:16px;margin-top:32px;padding:18px;background:#f6f7f9;border-radius:10px;">';
		$box .= '<div class="dicestack-author-avatar" style="flex-shrink:0;">' . $avatar . '</div>';
		$box .= '<div class="dicestack-author-info">';
		$box .= '<strong style="display:block;font-size:16px;">' . esc_html( $name ) . '</strong>';
		$box .= '<p style="margin:6px 0;">' . esc_html( $bio ) . '</p>';
		if ( $url ) {
			$box .= '<a href="' . esc_url( $url ) . '" rel="noopener" target="_blank" style="color:#0aa2c0;">' . esc_html__( 'Website', 'dicestack' ) . '</a>';
		}
		$box .= '</div></div>';

		return $box;
	}
}
