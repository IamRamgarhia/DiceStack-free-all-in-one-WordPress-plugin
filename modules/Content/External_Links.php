<?php
/**
 * External Links module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Makes external links in content open in a new tab and adds safe rel
 * attributes (noopener, noreferrer, optional nofollow).
 */
final class External_Links extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'external_links';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'External link handler', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Open external links in a new tab and add safe rel attributes automatically.', 'dicestack' );
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
		return 'external-link';
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
				'key'     => 'new_tab',
				'label'   => __( 'Open in a new tab', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'nofollow',
				'label'   => __( 'Add rel="nofollow"', 'dicestack' ),
				'type'    => 'toggle',
				'default' => false,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'the_content', array( $this, 'process' ), 15 );
	}

	/**
	 * Rewrite external anchors.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function process( $content ) {
		if ( strpos( $content, '<a ' ) === false ) {
			return $content;
		}
		$host     = wp_parse_url( home_url(), PHP_URL_HOST );
		$new_tab  = ! empty( $this->get_setting( 'new_tab', true ) );
		$nofollow = ! empty( $this->get_setting( 'nofollow', false ) );

		return preg_replace_callback(
			'/<a\s[^>]*href=("|\')(https?:\/\/[^"\']+)\1[^>]*>/i',
			function ( $m ) use ( $host, $new_tab, $nofollow ) {
				$tag      = $m[0];
				$link_host = wp_parse_url( $m[2], PHP_URL_HOST );
				if ( ! $link_host || $link_host === $host ) {
					return $tag; // internal link.
				}
				$rel = array( 'noopener', 'noreferrer' );
				if ( $nofollow ) {
					$rel[] = 'nofollow';
				}
				if ( ! preg_match( '/\brel=/i', $tag ) ) {
					$tag = preg_replace( '/<a\s/i', '<a rel="' . esc_attr( implode( ' ', $rel ) ) . '" ', $tag, 1 );
				}
				if ( $new_tab && ! preg_match( '/\btarget=/i', $tag ) ) {
					$tag = preg_replace( '/<a\s/i', '<a target="_blank" ', $tag, 1 );
				}
				return $tag;
			},
			$content
		);
	}
}
