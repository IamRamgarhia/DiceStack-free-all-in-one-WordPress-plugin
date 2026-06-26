<?php
/**
 * FAQ Schema module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Renders an accessible FAQ block and matching FAQPage JSON-LD via
 * [dicestack_faq] ... [/dicestack_faq] with one "Question|Answer" per line.
 */
final class FAQ_Schema extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'faq_schema';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'FAQ with schema', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add an FAQ section with rich-result schema using [dicestack_faq].', 'dicestack' );
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
	public function replaces() {
		return 'premium schema plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 25,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 0,
			'external_http' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_shortcode( 'dicestack_faq', array( $this, 'render' ) );
	}

	/**
	 * Render the FAQ list and JSON-LD.
	 *
	 * @param array  $atts    Attributes.
	 * @param string $content Enclosed content (Question|Answer per line).
	 * @return string
	 */
	public function render( $atts, $content = '' ) {
		$content = trim( (string) $content );
		if ( '' === $content ) {
			return '';
		}

		$pairs = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $content ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || strpos( $line, '|' ) === false ) {
				continue;
			}
			list( $q, $a ) = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( '' !== $q && '' !== $a ) {
				$pairs[] = array( $q, $a );
			}
		}
		if ( empty( $pairs ) ) {
			return '';
		}

		$html = '<div class="dicestack-faq">';
		$ld   = array();
		foreach ( $pairs as $pair ) {
			$html .= '<details style="border:1px solid #e4e7ec;border-radius:8px;padding:10px 14px;margin-bottom:8px;">';
			$html .= '<summary style="cursor:pointer;font-weight:500;">' . esc_html( $pair[0] ) . '</summary>';
			$html .= '<div style="margin-top:8px;">' . wp_kses_post( wpautop( $pair[1] ) ) . '</div>';
			$html .= '</details>';

			$ld[] = array(
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( $pair[0] ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $pair[1] ),
				),
			);
		}
		$html .= '</div>';

		$schema = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $ld,
		);
		$html .= '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>';

		return $html;
	}
}
