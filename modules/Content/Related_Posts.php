<?php
/**
 * Related Posts module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Appends related posts (by shared categories) below single posts. Replaces
 * YARPP's core behaviour.
 */
final class Related_Posts extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'related_posts';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Related posts', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Keep readers on your site with related posts shown below each article.', 'dicestack' );
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
		return 'copy';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium related-posts plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 55,
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
				'key'     => 'title',
				'label'   => __( 'Heading', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'Related posts', 'dicestack' ),
			),
			array(
				'key'     => 'count',
				'label'   => __( 'Number to show', 'dicestack' ),
				'type'    => 'number',
				'default' => 3,
				'min'     => 1,
				'max'     => 12,
				'step'    => 1,
			),
			array(
				'key'     => 'position',
				'label'   => __( 'Where to show', 'dicestack' ),
				'type'    => 'select',
				'default' => 'after',
				'options' => array(
					'after'  => __( 'After the post content', 'dicestack' ),
					'before' => __( 'Before the post content', 'dicestack' ),
					'manual' => __( 'Only where I place [dicestack_related]', 'dicestack' ),
				),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'the_content', array( $this, 'append' ), 40 );
		add_shortcode( 'dicestack_related', array( $this, 'shortcode' ) );
	}

	/**
	 * Shortcode handler — place the block anywhere.
	 *
	 * @return string
	 */
	public function shortcode() {
		return $this->build();
	}

	/**
	 * Auto-place the block before/after content (unless set to manual).
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
		$html = $this->build();
		if ( '' === $html ) {
			return $content;
		}
		return ( 'before' === $pos ) ? $html . $content : $content . $html;
	}

	/**
	 * Build the related-posts HTML.
	 *
	 * @return string
	 */
	public function build() {
		if ( ! is_singular( 'post' ) ) {
			return '';
		}
		$post_id = get_the_ID();
		$cats    = wp_get_post_categories( $post_id );
		$count   = (int) $this->get_setting( 'count', 3 );

		$args = array(
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- excluding only the current post; small bounded query.
			'post__not_in'        => array( $post_id ),
			'posts_per_page'      => $count,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
		if ( ! empty( $cats ) ) {
			$args['category__in'] = $cats;
		}

		$query = new \WP_Query( $args );
		if ( ! $query->have_posts() ) {
			return '';
		}

		$html  = '<section class="dicestack-related" style="margin-top:32px;border-top:1px solid #e4e7ec;padding-top:20px;">';
		$html .= '<h3 style="margin:0 0 14px;">' . esc_html( $this->get_setting( 'title', __( 'Related posts', 'dicestack' ) ) ) . '</h3>';
		$html .= '<ul style="list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">';

		while ( $query->have_posts() ) {
			$query->the_post();
			$thumb = has_post_thumbnail() ? get_the_post_thumbnail( get_the_ID(), 'medium', array( 'style' => 'width:100%;height:auto;border-radius:8px;' ) ) : '';
			$html .= '<li>';
			$html .= '<a href="' . esc_url( get_permalink() ) . '" style="text-decoration:none;color:inherit;">';
			$html .= $thumb;
			$html .= '<span style="display:block;margin-top:8px;font-weight:500;">' . esc_html( get_the_title() ) . '</span>';
			$html .= '</a></li>';
		}
		wp_reset_postdata();

		$html .= '</ul></section>';
		return $html;
	}
}
