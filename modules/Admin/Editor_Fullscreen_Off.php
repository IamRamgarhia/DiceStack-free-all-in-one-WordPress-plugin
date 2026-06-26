<?php
/**
 * Editor Fullscreen Off module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Turns off the block editor's distracting fullscreen mode by default, so the
 * admin menu stays visible when editing.
 */
final class Editor_Fullscreen_Off extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'editor_fullscreen_off';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Disable editor fullscreen', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Keep the admin menu visible by turning off the editor\'s fullscreen mode.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'admin';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'layout-grid';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 10,
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
		add_action( 'enqueue_block_editor_assets', array( $this, 'script' ) );
	}

	/**
	 * Inject a small script that disables fullscreen once on load.
	 *
	 * @return void
	 */
	public function script() {
		$js = "wp.domReady(function(){try{if(wp.data.select('core/edit-post').isFeatureActive('fullscreenMode')){wp.data.dispatch('core/edit-post').toggleFeature('fullscreenMode');}}catch(e){}});";
		wp_add_inline_script( 'wp-edit-post', $js );
	}
}
