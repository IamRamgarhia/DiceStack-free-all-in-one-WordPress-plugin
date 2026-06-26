<?php
/**
 * Breadcrumbs module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Outputs breadcrumb navigation via the [dicestack_breadcrumbs] shortcode. Pair
 * with the Schema module for BreadcrumbList rich results.
 */
final class Breadcrumbs extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'breadcrumbs';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Breadcrumbs', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add breadcrumb navigation with [dicestack_breadcrumbs] to aid users and search engines.', 'dicestack' );
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
		return 'arrow-back-up';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 30,
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
				'key'     => 'separator',
				'label'   => __( 'Separator', 'dicestack' ),
				'type'    => 'text',
				'default' => '/',
			),
			array(
				'key'     => 'home_label',
				'label'   => __( 'Home label', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'Home', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_shortcode( 'dicestack_breadcrumbs', array( $this, 'render' ) );
	}

	/**
	 * Build the breadcrumb trail.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		if ( is_front_page() ) {
			return '';
		}

		$sep   = ' <span class="sep">' . esc_html( (string) $this->get_setting( 'separator', '/' ) ) . '</span> ';
		$home  = esc_html( (string) $this->get_setting( 'home_label', __( 'Home', 'dicestack' ) ) );
		$crumbs = array();

		$crumbs[] = '<a href="' . esc_url( home_url( '/' ) ) . '">' . $home . '</a>';

		if ( is_singular() ) {
			$post_type = get_post_type();
			if ( 'post' === $post_type ) {
				$cats = get_the_category();
				if ( ! empty( $cats ) ) {
					$crumbs[] = '<a href="' . esc_url( get_category_link( $cats[0]->term_id ) ) . '">' . esc_html( $cats[0]->name ) . '</a>';
				}
			}
			$crumbs[] = '<span class="current">' . esc_html( get_the_title() ) . '</span>';
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$crumbs[] = '<span class="current">' . esc_html( single_term_title( '', false ) ) . '</span>';
		} elseif ( is_search() ) {
			$crumbs[] = '<span class="current">' . esc_html__( 'Search results', 'dicestack' ) . '</span>';
		} elseif ( is_404() ) {
			$crumbs[] = '<span class="current">' . esc_html__( 'Not found', 'dicestack' ) . '</span>';
		} elseif ( is_archive() ) {
			$crumbs[] = '<span class="current">' . esc_html( get_the_archive_title() ) . '</span>';
		}

		return '<nav class="dicestack-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'dicestack' ) . '">' . implode( $sep, $crumbs ) . '</nav>';
	}
}
