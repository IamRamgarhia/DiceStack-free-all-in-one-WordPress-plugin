<?php
/**
 * Classic Editor module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Restores the classic TinyMCE editor by disabling the block editor. Replaces
 * the Classic Editor plugin.
 */
final class Classic_Editor extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'classic_editor';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Classic editor', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Bring back the classic TinyMCE editor instead of the block editor.', 'dicestack' );
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
		return 'file-code';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'classic-editor plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 15,
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
		add_filter( 'use_block_editor_for_post', '__return_false', 100 );
		add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
		// Hide the "Try Gutenberg" prompt and keep widgets classic.
		add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' );
		add_filter( 'use_widgets_block_editor', '__return_false' );
	}
}
