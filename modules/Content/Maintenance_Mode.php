<?php
/**
 * Maintenance Mode module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Content;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Shows a maintenance / coming-soon page to visitors while letting logged-in
 * admins see the live site. Replaces SeedProd's basic coming-soon mode.
 */
final class Maintenance_Mode extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'maintenance_mode';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Maintenance mode', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Show a coming-soon page to visitors while you work, with admins still able to browse.', 'dicestack' );
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
		return 'tool';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium coming-soon plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 35,
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
				'key'     => 'heading',
				'label'   => __( 'Heading', 'dicestack' ),
				'type'    => 'text',
				'default' => __( 'We\'ll be right back', 'dicestack' ),
			),
			array(
				'key'     => 'message',
				'label'   => __( 'Message', 'dicestack' ),
				'type'    => 'textarea',
				'default' => __( 'Our site is undergoing scheduled maintenance. Please check back soon.', 'dicestack' ),
			),
			array(
				'key'     => 'bg_color',
				'label'   => __( 'Background colour', 'dicestack' ),
				'type'    => 'color',
				'default' => '#1b2a4a',
			),
			array(
				'key'     => 'status',
				'label'   => __( 'Tell search engines', 'dicestack' ),
				'type'    => 'select',
				'default' => 'maintenance',
				'options' => array(
					'maintenance' => __( '503 — temporary maintenance (recommended)', 'dicestack' ),
					'coming'      => __( '200 — coming soon (new site)', 'dicestack' ),
				),
			),
			array(
				'key'     => 'bypass_key',
				'label'   => __( 'Shareable bypass key', 'dicestack' ),
				'type'    => 'text',
				'default' => '',
				'help'    => __( 'Set a secret word, then share yoursite.com/?dicestack_preview=THATWORD to let clients preview the live site. The bypass is remembered for 12 hours.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'template_redirect', array( $this, 'maybe_show' ), 0 );
	}

	/**
	 * Show the maintenance screen to non-admins.
	 *
	 * @return void
	 */
	public function maybe_show() {
		if ( current_user_can( 'manage_options' ) || is_user_logged_in() ) {
			return;
		}
		// Don't block the login page or AJAX/cron.
		if ( wp_doing_ajax() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}

		// Shareable bypass: ?dicestack_preview=KEY sets a 12h cookie.
		$key = trim( (string) $this->get_setting( 'bypass_key', '' ) );
		if ( '' !== $key ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- secret-key preview link, no state change beyond a preview cookie.
			if ( isset( $_GET['dicestack_preview'] ) && hash_equals( $key, sanitize_text_field( wp_unslash( $_GET['dicestack_preview'] ) ) ) ) {
				if ( ! headers_sent() ) {
					setcookie( 'dicestack_preview', $key, time() + 12 * HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
				}
				return;
			}
			if ( isset( $_COOKIE['dicestack_preview'] ) && hash_equals( $key, sanitize_text_field( wp_unslash( $_COOKIE['dicestack_preview'] ) ) ) ) {
				return;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		$s     = $this->get_settings();
		$code  = ( 'coming' === $s['status'] ) ? 200 : 503;
		$color = sanitize_hex_color( (string) $s['bg_color'] );
		$color = $color ? $color : '#1b2a4a';

		if ( 503 === $code ) {
			status_header( 503 );
			header( 'Retry-After: 3600' );
		}
		nocache_headers();

		$heading = esc_html( $s['heading'] );
		$message = esc_html( $s['message'] );
		$title   = esc_html( get_bloginfo( 'name' ) );

		echo '<!DOCTYPE html><html ' . get_language_attributes() . '><head><meta charset="utf-8" />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<meta name="viewport" content="width=device-width, initial-scale=1" />';
		echo '<title>' . $heading . ' — ' . $title . '</title>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<style>body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:' . esc_attr( $color ) . ';color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;padding:24px}.dicestack-mm{max-width:560px}.dicestack-mm h1{font-size:34px;margin:0 0 14px}.dicestack-mm p{font-size:17px;line-height:1.6;opacity:.85}</style>';
		echo '</head><body><div class="dicestack-mm"><h1>' . $heading . '</h1><p>' . $message . '</p></div></body></html>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}
