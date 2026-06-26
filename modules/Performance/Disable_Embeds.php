<?php
/**
 * Disable Embeds module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Performance;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the wp-embed.js script and oEmbed discovery that WordPress adds for
 * embedding other WordPress sites — rarely needed and adds weight to every page.
 */
final class Disable_Embeds extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'disable_embeds';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Disable oEmbeds', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Remove the wp-embed script and oEmbed discovery tags from every page.', 'dicestack' );
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
		return 'bolt';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 16,
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
		add_action( 'init', array( $this, 'remove_embeds' ), 9999 );
	}

	/**
	 * Strip embed scripts and handlers.
	 *
	 * @return void
	 */
	public function remove_embeds() {
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );
		add_filter( 'embed_oembed_discover', '__return_false' );
		add_action( 'wp_footer', array( $this, 'dequeue' ) );
	}

	/**
	 * Dequeue the embed script.
	 *
	 * @return void
	 */
	public function dequeue() {
		wp_dequeue_script( 'wp-embed' );
	}
}
