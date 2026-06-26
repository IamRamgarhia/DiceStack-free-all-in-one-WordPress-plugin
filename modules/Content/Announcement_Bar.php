<?php
/**
 * Announcement Bar module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * A dismissible site-wide notification bar for promos and announcements.
 * Ships its CSS/JS inline (no extra requests). Replaces Hello Bar / WP
 * Notification Bar.
 */
final class Announcement_Bar extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'announcement_bar';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Announcement bar', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'A dismissible top bar for sales, shipping notices, or announcements.', 'dicestack' );
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
		return 'speakerphone';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium notification-bar plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 30,
			'front_js_kb'   => 0.5,
			'front_css_kb'  => 0.5,
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
				'key'     => 'text',
				'label'   => __( 'Message', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'Free shipping on all orders this week!', 'dicestack' ),
			),
			array(
				'key'     => 'link',
				'label'   => __( 'Link URL (optional)', 'dicestack' ),
				'type'    => 'url',
				'default' => '',
			),
			array(
				'key'     => 'bg_color',
				'label'   => __( 'Background colour', 'dicestack' ),
				'type'    => 'color',
				'default' => '#0aa2c0',
			),
			array(
				'key'     => 'text_color',
				'label'   => __( 'Text colour', 'dicestack' ),
				'type'    => 'color',
				'default' => '#ffffff',
			),
			array(
				'key'     => 'dismissible',
				'label'   => __( 'Let visitors dismiss it', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'wp_body_open', array( $this, 'render' ) );
		// Fallback for themes without wp_body_open.
		add_action( 'wp_footer', array( $this, 'render_fallback' ) );
	}

	/**
	 * Track whether the bar has already been printed this request.
	 *
	 * @var bool
	 */
	private $printed = false;

	/**
	 * Render at the top of the body.
	 *
	 * @return void
	 */
	public function render() {
		if ( $this->printed ) {
			return;
		}
		$text = trim( (string) $this->get_setting( 'text', '' ) );
		if ( '' === $text ) {
			return;
		}

		$this->printed = true;
		$bg            = sanitize_hex_color( (string) $this->get_setting( 'bg_color', '#0aa2c0' ) ) ?: '#0aa2c0';
		$fg            = sanitize_hex_color( (string) $this->get_setting( 'text_color', '#ffffff' ) ) ?: '#ffffff';
		$link          = esc_url( (string) $this->get_setting( 'link', '' ) );
		$dismiss       = ! empty( $this->get_setting( 'dismissible', true ) );

		$content = $link ? '<a href="' . $link . '" style="color:' . esc_attr( $fg ) . ';text-decoration:underline;">' . esc_html( $text ) . '</a>' : esc_html( $text );

		echo '<div id="dicestack-bar" style="background:' . esc_attr( $bg ) . ';color:' . esc_attr( $fg ) . ';text-align:center;padding:10px 40px;position:relative;font-size:14px;">' . $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- parts escaped above.
		if ( $dismiss ) {
			echo '<button id="dicestack-bar-x" aria-label="' . esc_attr__( 'Dismiss', 'dicestack' ) . '" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:0;color:' . esc_attr( $fg ) . ';font-size:18px;cursor:pointer;line-height:1;">&times;</button>';
			echo '<script>(function(){try{if(localStorage.getItem("dicestack_bar_closed")){document.getElementById("dicestack-bar").style.display="none";}var x=document.getElementById("dicestack-bar-x");if(x){x.addEventListener("click",function(){document.getElementById("dicestack-bar").style.display="none";try{localStorage.setItem("dicestack_bar_closed","1");}catch(e){}});}}catch(e){}})();</script>';
		}
		echo '</div>';
	}

	/**
	 * If the theme never fired wp_body_open, render at footer as a fallback.
	 *
	 * @return void
	 */
	public function render_fallback() {
		if ( ! $this->printed ) {
			$this->render();
		}
	}
}
