<?php
/**
 * Lazy Loading module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds native browser lazy-loading to images and iframes. WordPress lazy-loads
 * images by default, but not iframes (YouTube/Maps embeds), which are heavy.
 */
final class Lazy_Loading extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'lazy_loading';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Lazy loading', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Defer off-screen images and iframes until the visitor scrolls to them.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'performance';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'photo';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 35,
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
				'key'     => 'iframes',
				'label'   => __( 'Lazy-load iframes (YouTube, Maps, etc.)', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'wp_lazy_loading_enabled', '__return_true' );

		if ( ! empty( $this->get_setting( 'iframes', true ) ) ) {
			add_filter( 'the_content', array( $this, 'lazy_iframes' ), 25 );
		}
	}

	/**
	 * Add loading="lazy" to iframes that don't already declare it.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function lazy_iframes( $content ) {
		if ( ! is_string( $content ) || strpos( $content, '<iframe' ) === false ) {
			return $content;
		}
		return preg_replace_callback(
			'/<iframe\b(?![^>]*\bloading=)([^>]*)>/i',
			static function ( $m ) {
				return '<iframe loading="lazy"' . $m[1] . '>';
			},
			$content
		);
	}
}
