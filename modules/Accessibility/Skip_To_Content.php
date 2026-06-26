<?php
/**
 * Skip to Content module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Accessibility;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a keyboard-accessible "Skip to content" link at the top of the page —
 * a baseline WCAG requirement many themes omit.
 */
final class Skip_To_Content extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'skip_to_content';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Skip to content link', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Add a keyboard "skip to main content" link for screen-reader and keyboard users.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'accessibility';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'accessible';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 12,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0.2,
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
				'key'     => 'target',
				'label'   => __( 'Target element ID', 'dicestack' ),
				'type'    => 'text',
				'default' => 'main',
				'help'    => __( 'Most themes use "main" or "content". Enter without the # symbol.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_body_open', array( $this, 'render' ), 1 );
	}

	/**
	 * Output the skip link and its focus styles.
	 *
	 * @return void
	 */
	public function render() {
		$target = sanitize_html_class( (string) $this->get_setting( 'target', 'main' ) );
		$target = $target ? $target : 'main';
		echo '<a class="dicestack-skip" href="#' . esc_attr( $target ) . '" style="position:absolute;left:-9999px;top:0;background:#1b2a4a;color:#fff;padding:10px 16px;z-index:100000;border-radius:0 0 6px 0;" onfocus="this.style.left=\'0\'" onblur="this.style.left=\'-9999px\'">' . esc_html__( 'Skip to content', 'dicestack' ) . '</a>';
	}
}
