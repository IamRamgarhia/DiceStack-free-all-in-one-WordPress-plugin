<?php
/**
 * Twitter Handles module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\SEO;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Adds twitter:site and twitter:creator meta so X shows your handle on shared
 * cards.
 */
final class Twitter_Handles extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'twitter_handles';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Twitter/X handles', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Attribute shared link cards to your X account with twitter:site meta.', 'dicestack' );
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
		return 'world';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 10,
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
				'key'     => 'site',
				'label'   => __( 'Site @handle', 'dicestack' ),
				'type'    => 'text',
				'default' => '',
				'help'    => __( 'Without the @, e.g. dicecodes', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_head', array( $this, 'output' ), 3 );
	}

	/**
	 * Print the twitter:site tag.
	 *
	 * @return void
	 */
	public function output() {
		$handle = ltrim( trim( (string) $this->get_setting( 'site', '' ) ), '@' );
		if ( '' !== $handle ) {
			echo '<meta name="twitter:site" content="@' . esc_attr( $handle ) . '" />' . "\n";
		}
	}
}
