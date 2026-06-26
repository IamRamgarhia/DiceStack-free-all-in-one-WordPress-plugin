<?php
/**
 * Copy Protection module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * A light deterrent against casual content theft: disables right-click and text
 * selection on the front end for logged-out visitors. Not foolproof (nothing
 * client-side is), but stops casual copying.
 */
final class Copy_Protection extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'copy_protection';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Content copy protection', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Deter casual copying by disabling right-click and text selection for visitors.', 'dicestack' );
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
		return 'lock';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 14,
			'front_js_kb'   => 0.4,
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
				'key'     => 'disable_selection',
				'label'   => __( 'Also disable text selection', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_footer', array( $this, 'render' ) );
	}

	/**
	 * Output the protection script (skips logged-in users so editors can work).
	 *
	 * @return void
	 */
	public function render() {
		if ( is_user_logged_in() ) {
			return;
		}
		$selection = ! empty( $this->get_setting( 'disable_selection', true ) );
		if ( $selection ) {
			echo '<style>body{-webkit-user-select:none;-moz-user-select:none;user-select:none}body input,body textarea{user-select:text}</style>';
		}
		echo '<script>document.addEventListener("contextmenu",function(e){e.preventDefault();},false);</script>';
	}
}
