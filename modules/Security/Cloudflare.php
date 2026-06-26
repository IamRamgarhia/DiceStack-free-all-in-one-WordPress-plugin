<?php
/**
 * Cloudflare integration module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Security;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Controls Cloudflare from the dashboard: toggle "I'm Under Attack" mode, purge
 * the CDN cache, and switch on Development Mode. Needs a Cloudflare API token and
 * Zone ID — each field links to exactly where to get it.
 */
final class Cloudflare extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'cloudflare';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Cloudflare control', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Toggle Under Attack mode, purge cache, and dev mode on Cloudflare from wp-admin.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'security';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'cloud';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 30,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 1,
			'external_http' => 1,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function external_service() {
		return array(
			'service' => 'Cloudflare API',
			'url'     => 'https://www.cloudflare.com/privacypolicy/',
			'data'    => __( 'Your API token and zone ID are sent to Cloudflare to manage your site settings.', 'dicestack' ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function settings_schema() {
		return array(
			array(
				'key'     => 'api_token',
				'label'   => __( 'Cloudflare API token', 'dicestack' ),
				'type'    => 'password',
				'default' => '',
				'help'    => __( 'Create a token with the "Zone.Cache Purge" and "Zone Settings: Edit" permissions.', 'dicestack' ),
				'guide'   => array(
					'url'   => 'https://dash.cloudflare.com/profile/api-tokens',
					'label' => __( 'Create an API token', 'dicestack' ),
				),
			),
			array(
				'key'     => 'zone_id',
				'label'   => __( 'Zone ID', 'dicestack' ),
				'type'    => 'text',
				'default' => '',
				'help'    => __( 'Found on your domain\'s Overview page in Cloudflare (right column).', 'dicestack' ),
				'guide'   => array(
					'url'   => 'https://developers.cloudflare.com/fundamentals/setup/find-account-and-zone-ids/',
					'label' => __( 'Where to find your Zone ID', 'dicestack' ),
				),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		if ( ! is_admin() ) {
			return;
		}
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_post_dicestack_cf_action', array( $this, 'handle_action' ) );
		add_action( 'admin_post_dicestack_cf_save', array( $this, 'handle_save' ) );
	}

	/**
	 * Save the Cloudflare credentials entered on the control page.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dicestack_cf_save' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}
		$this->save_settings(
			array(
				'api_token' => isset( $_POST['api_token'] ) ? wp_unslash( $_POST['api_token'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- credential, sanitised in save_settings().
				'zone_id'   => isset( $_POST['zone_id'] ) ? sanitize_text_field( wp_unslash( $_POST['zone_id'] ) ) : '',
			)
		);
		wp_safe_redirect( admin_url( 'admin.php?page=dicestack-cloudflare&saved=1' ) );
		exit;
	}

	/**
	 * Call the Cloudflare API.
	 *
	 * @param string $method   HTTP method.
	 * @param string $endpoint Endpoint after the zone (e.g. settings/security_level).
	 * @param array  $body     Request body.
	 * @return array|\WP_Error Decoded response or error.
	 */
	private function api( $method, $endpoint, $body = array() ) {
		$token = trim( (string) $this->get_setting( 'api_token', '' ) );
		$zone  = trim( (string) $this->get_setting( 'zone_id', '' ) );
		if ( '' === $token || '' === $zone ) {
			return new \WP_Error( 'cf_config', __( 'Add your Cloudflare API token and Zone ID first.', 'dicestack' ) );
		}
		$args = array(
			'method'  => $method,
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
		);
		if ( ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}
		$response = wp_remote_request( 'https://api.cloudflare.com/client/v4/zones/' . rawurlencode( $zone ) . '/' . $endpoint, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Register the control page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'dicestack',
			__( 'Cloudflare', 'dicestack' ),
			__( 'Cloudflare', 'dicestack' ),
			'manage_options',
			'dicestack-cloudflare',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handle a Cloudflare action button.
	 *
	 * @return void
	 */
	public function handle_action() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dicestack_cf' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}
		$do = isset( $_POST['cf'] ) ? sanitize_key( wp_unslash( $_POST['cf'] ) ) : '';

		switch ( $do ) {
			case 'attack_on':
				$this->api( 'PATCH', 'settings/security_level', array( 'value' => 'under_attack' ) );
				break;
			case 'attack_off':
				$this->api( 'PATCH', 'settings/security_level', array( 'value' => 'medium' ) );
				break;
			case 'purge':
				$this->api( 'POST', 'purge_cache', array( 'purge_everything' => true ) );
				break;
			case 'dev_on':
				$this->api( 'PATCH', 'settings/development_mode', array( 'value' => 'on' ) );
				break;
			case 'dev_off':
				$this->api( 'PATCH', 'settings/development_mode', array( 'value' => 'off' ) );
				break;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=dicestack-cloudflare&done=1' ) );
		exit;
	}

	/**
	 * A single action button form.
	 *
	 * @param string $action Action key.
	 * @param string $label  Button label.
	 * @param bool   $primary Primary style.
	 * @return string
	 */
	private function button( $action, $label, $primary = false ) {
		$html  = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin:0 6px 8px 0;">';
		$html .= wp_nonce_field( 'dicestack_cf', '_wpnonce', true, false );
		$html .= '<input type="hidden" name="action" value="dicestack_cf_action" />';
		$html .= '<input type="hidden" name="cf" value="' . esc_attr( $action ) . '" />';
		$html .= '<button type="submit" class="button ' . ( $primary ? 'button-primary' : '' ) . '">' . esc_html( $label ) . '</button>';
		$html .= '</form>';
		return $html;
	}

	/**
	 * Render the Cloudflare control page.
	 *
	 * @return void
	 */
	public function render_page() {
		$configured = ( '' !== trim( (string) $this->get_setting( 'api_token', '' ) ) && '' !== trim( (string) $this->get_setting( 'zone_id', '' ) ) );

		echo '<div class="wrap"><h1>' . esc_html__( 'Cloudflare control', 'dicestack' ) . '</h1>';
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display-only flags.
		if ( isset( $_GET['done'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Cloudflare updated.', 'dicestack' ) . '</p></div>';
		}
		if ( isset( $_GET['saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Cloudflare connection saved.', 'dicestack' ) . '</p></div>';
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Connection settings — entered right here so the tool is self-contained.
		$token = (string) $this->get_setting( 'api_token', '' );
		$zone  = (string) $this->get_setting( 'zone_id', '' );
		echo '<h2>' . esc_html__( 'Connection', 'dicestack' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="max-width:560px;">';
		wp_nonce_field( 'dicestack_cf_save' );
		echo '<input type="hidden" name="action" value="dicestack_cf_save" />';
		echo '<p><label><strong>' . esc_html__( 'Cloudflare API token', 'dicestack' ) . '</strong><br/>';
		echo '<input type="password" name="api_token" value="' . esc_attr( $token ) . '" autocomplete="off" style="width:100%;" placeholder="' . esc_attr__( 'Paste your API token', 'dicestack' ) . '" /></label><br/>';
		echo '<span class="description">' . esc_html__( 'Create a token with the "Zone.Cache Purge" and "Zone Settings: Edit" permissions.', 'dicestack' ) . ' <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank" rel="noopener">' . esc_html__( 'Create an API token', 'dicestack' ) . '</a></span></p>';
		echo '<p><label><strong>' . esc_html__( 'Zone ID', 'dicestack' ) . '</strong><br/>';
		echo '<input type="text" name="zone_id" value="' . esc_attr( $zone ) . '" style="width:100%;" placeholder="' . esc_attr__( 'e.g. 0a1b2c3d4e5f...', 'dicestack' ) . '" /></label><br/>';
		echo '<span class="description">' . esc_html__( "Found on your domain's Overview page in Cloudflare (right column).", 'dicestack' ) . ' <a href="https://developers.cloudflare.com/fundamentals/setup/find-account-and-zone-ids/" target="_blank" rel="noopener">' . esc_html__( 'Where to find it', 'dicestack' ) . '</a></span></p>';
		echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Save connection', 'dicestack' ) . '</button></p>';
		echo '</form>';

		if ( ! $configured ) {
			echo '<p style="color:#6b7280;"><em>' . esc_html__( 'Enter your API token and Zone ID above to unlock the controls below.', 'dicestack' ) . '</em></p></div>';
			return;
		}

		echo '<hr/><h2>' . esc_html__( 'Under Attack mode', 'dicestack' ) . '</h2>';
		echo '<p>' . esc_html__( 'Shows a 5-second challenge to every visitor — use during a DDoS or attack.', 'dicestack' ) . '</p>';
		echo $this->button( 'attack_on', __( 'Enable Under Attack mode', 'dicestack' ), true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with escaping.
		echo $this->button( 'attack_off', __( 'Back to normal', 'dicestack' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo '<h2>' . esc_html__( 'Cache', 'dicestack' ) . '</h2>';
		echo $this->button( 'purge', __( 'Purge everything', 'dicestack' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo '<h2>' . esc_html__( 'Development mode', 'dicestack' ) . '</h2>';
		echo '<p>' . esc_html__( 'Temporarily bypasses the cache for 3 hours while you make changes.', 'dicestack' ) . '</p>';
		echo $this->button( 'dev_on', __( 'Turn on dev mode', 'dicestack' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->button( 'dev_off', __( 'Turn off dev mode', 'dicestack' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}
}
