<?php
/**
 * Table of Contents module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Builds an anchored table of contents from a post's headings. Replaces
 * Easy/Joli Table of Contents.
 */
final class Table_Of_Contents extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'table_of_contents';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Table of contents', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Auto-generate an anchored table of contents from your post headings.', 'dicestack' );
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
	public function replaces() {
		return 'premium table-of-contents plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 45,
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
				'key'     => 'title',
				'label'   => __( 'Heading', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'Table of contents', 'dicestack' ),
			),
			array(
				'key'     => 'min_headings',
				'label'   => __( 'Minimum headings to show the TOC', 'dicestack' ),
				'type'    => 'number',
				'default' => 3,
				'min'     => 2,
				'max'     => 20,
				'step'    => 1,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'the_content', array( $this, 'inject' ), 30 );
	}

	/**
	 * Inject the TOC before the content on single posts.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function inject( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		if ( strpos( $content, '<h2' ) === false && strpos( $content, '<h3' ) === false ) {
			return $content;
		}

		$min = (int) $this->get_setting( 'min_headings', 3 );

		// Find h2/h3 headings.
		if ( ! preg_match_all( '/<h([23])([^>]*)>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER ) ) {
			return $content;
		}
		if ( count( $matches ) < $min ) {
			return $content;
		}

		$items   = array();
		$used    = array();
		$counter = 0;

		// Add IDs to headings and collect TOC items.
		$content = preg_replace_callback(
			'/<h([23])([^>]*)>(.*?)<\/h\1>/is',
			function ( $m ) use ( &$items, &$used, &$counter ) {
				$level = (int) $m[1];
				$text  = wp_strip_all_tags( $m[3] );
				$slug  = sanitize_title( $text );
				if ( '' === $slug ) {
					$slug = 'section';
				}
				// Ensure uniqueness.
				$base = $slug;
				$i    = 2;
				while ( isset( $used[ $slug ] ) ) {
					$slug = $base . '-' . $i;
					$i++;
				}
				$used[ $slug ] = true;
				$counter++;

				$items[] = array(
					'level' => $level,
					'text'  => $text,
					'slug'  => $slug,
				);

				// Skip if the heading already has an id attribute.
				if ( preg_match( '/\bid=/i', $m[2] ) ) {
					return $m[0];
				}
				return '<h' . $m[1] . $m[2] . ' id="' . esc_attr( $slug ) . '">' . $m[3] . '</h' . $m[1] . '>';
			},
			$content
		);

		$toc  = '<nav class="dicestack-toc" style="background:#f6f7f9;border:1px solid #e4e7ec;border-radius:8px;padding:16px 20px;margin:0 0 24px;">';
		$toc .= '<strong style="display:block;margin-bottom:8px;">' . esc_html( $this->get_setting( 'title', __( 'Table of contents', 'dicestack' ) ) ) . '</strong>';
		$toc .= '<ul style="margin:0;padding-left:18px;">';
		foreach ( $items as $item ) {
			$indent = ( 3 === $item['level'] ) ? ' style="margin-left:14px;"' : '';
			$toc   .= '<li' . $indent . '><a href="#' . esc_attr( $item['slug'] ) . '">' . esc_html( $item['text'] ) . '</a></li>';
		}
		$toc .= '</ul></nav>';

		return $toc . $content;
	}
}
