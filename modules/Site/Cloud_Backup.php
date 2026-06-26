<?php
/**
 * Cloud Backup module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Site;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Sends backups created by the Backup & Restore module to an off-site location.
 * The keyless destinations (FTP/FTPS, WebDAV, Email) need only credentials — no
 * developer API key. Google Drive uses a one-time connect (access token).
 */
final class Cloud_Backup extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'cloud_backup';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Cloud backup', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Send backups off-site to FTP, WebDAV (Nextcloud), email, or Google Drive.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'site';
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
	public function replaces() {
		return 'premium backup plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function external_service() {
		return array(
			'service' => __( 'Your chosen destination (FTP/WebDAV/email/Google Drive)', 'dicestack' ),
			'url'     => 'https://dicecodes.com/dicestack-wordpress-plugin/',
			'data'    => __( 'Your backup archive is uploaded to the destination you configure.', 'dicestack' ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 40,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 1,
			'external_http' => 1,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function settings_schema() {
		return array(
			array(
				'key'     => 'destination',
				'label'   => __( 'Send backups to', 'dicestack' ),
				'type'    => 'select',
				'default' => 'none',
				'options' => array(
					'none'   => __( 'Nowhere (off)', 'dicestack' ),
					'ftp'    => __( 'FTP / FTPS (just host + login)', 'dicestack' ),
					'webdav' => __( 'WebDAV — Nextcloud/ownCloud (URL + login)', 'dicestack' ),
					'email'  => __( 'Email (small backups only)', 'dicestack' ),
					'gdrive' => __( 'Google Drive (one-time connect)', 'dicestack' ),
				),
			),
			// FTP.
			array( 'key' => 'ftp_host', 'label' => __( 'FTP host', 'dicestack' ), 'type' => 'text', 'default' => '' ),
			array( 'key' => 'ftp_port', 'label' => __( 'FTP port', 'dicestack' ), 'type' => 'number', 'default' => 21, 'min' => 1, 'max' => 65535, 'step' => 1 ),
			array( 'key' => 'ftp_user', 'label' => __( 'FTP username', 'dicestack' ), 'type' => 'text', 'default' => '' ),
			array( 'key' => 'ftp_pass', 'label' => __( 'FTP password', 'dicestack' ), 'type' => 'password', 'default' => '' ),
			array( 'key' => 'ftp_dir', 'label' => __( 'FTP folder', 'dicestack' ), 'type' => 'text', 'default' => '/', 'help' => __( 'Remote folder to store backups in.', 'dicestack' ) ),
			array( 'key' => 'ftp_ssl', 'label' => __( 'Use FTPS (secure)', 'dicestack' ), 'type' => 'toggle', 'default' => true ),
			// WebDAV.
			array( 'key' => 'webdav_url', 'label' => __( 'WebDAV URL', 'dicestack' ), 'type' => 'url', 'default' => '', 'help' => __( 'e.g. https://cloud.example.com/remote.php/dav/files/you/Backups/', 'dicestack' ) ),
			array( 'key' => 'webdav_user', 'label' => __( 'WebDAV username', 'dicestack' ), 'type' => 'text', 'default' => '' ),
			array( 'key' => 'webdav_pass', 'label' => __( 'WebDAV password / app password', 'dicestack' ), 'type' => 'password', 'default' => '' ),
			// Email.
			array( 'key' => 'email_to', 'label' => __( 'Email backups to', 'dicestack' ), 'type' => 'text', 'default' => get_option( 'admin_email' ) ),
			// Google Drive.
			array( 'key' => 'gdrive_token', 'label' => __( 'Google Drive access token', 'dicestack' ), 'type' => 'password', 'default' => '', 'help' => __( 'Click "Connect Google Drive" on the Cloud backup page to obtain this automatically.', 'dicestack' ) ),
			array( 'key' => 'gdrive_folder', 'label' => __( 'Google Drive folder ID (optional)', 'dicestack' ), 'type' => 'text', 'default' => '' ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( 'dicestack_backup_created', array( $this, 'upload' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_page' ) );
			add_action( 'admin_post_dicestack_cloud_test', array( $this, 'handle_test' ) );
			add_action( 'admin_init', array( $this, 'capture_gdrive_token' ) );
		}
	}

	/**
	 * Capture a Google Drive access token returned by the connector and save it.
	 *
	 * @return void
	 */
	public function capture_gdrive_token() {
		if ( ! current_user_can( 'manage_options' ) || empty( $_GET['dicestack_gdrive_token'] ) ) {
			return;
		}
		// Verify the state nonce we round-tripped through the connector so a
		// forged link cannot silently repoint backups to an attacker's Drive.
		$state = isset( $_GET['dicestack_state'] ) ? sanitize_text_field( wp_unslash( $_GET['dicestack_state'] ) ) : '';
		if ( ! wp_verify_nonce( $state, 'dicestack_gdrive_connect' ) ) {
			return;
		}
		$token    = sanitize_text_field( wp_unslash( $_GET['dicestack_gdrive_token'] ) );
		$settings = $this->get_settings();
		$settings['gdrive_token'] = $token;
		$settings['destination']  = 'gdrive';
		update_option( $this->settings_option_key(), $settings );

		wp_safe_redirect( admin_url( 'admin.php?page=dicestack-cloud' ) );
		exit;
	}

	/**
	 * Upload a backup file to the configured destination.
	 *
	 * @param string $file Absolute path to the backup zip.
	 * @return bool|\WP_Error
	 */
	public function upload( $file ) {
		$dest = $this->get_setting( 'destination', 'none' );
		if ( 'none' === $dest || ! is_file( $file ) ) {
			return false;
		}

		switch ( $dest ) {
			case 'ftp':
				$result = $this->upload_ftp( $file );
				break;
			case 'webdav':
				$result = $this->upload_webdav( $file );
				break;
			case 'email':
				$result = $this->upload_email( $file );
				break;
			case 'gdrive':
				$result = $this->upload_gdrive( $file );
				break;
			default:
				$result = false;
		}

		$ok = ( true === $result );
		update_option(
			'dicestack_cloud_last',
			array(
				'time'    => time(),
				'ok'      => $ok,
				'dest'    => $dest,
				'message' => is_wp_error( $result ) ? $result->get_error_message() : ( $ok ? __( 'Uploaded', 'dicestack' ) : __( 'Failed', 'dicestack' ) ),
			),
			false
		);
		return $result;
	}

	/**
	 * Upload via FTP/FTPS.
	 *
	 * @param string $file File path.
	 * @return bool|\WP_Error
	 */
	private function upload_ftp( $file ) {
		if ( ! function_exists( 'ftp_connect' ) ) {
			return new \WP_Error( 'no_ftp', __( 'PHP FTP support is not available on this server.', 'dicestack' ) );
		}
		$host = (string) $this->get_setting( 'ftp_host', '' );
		$port = (int) $this->get_setting( 'ftp_port', 21 );
		$ssl  = ! empty( $this->get_setting( 'ftp_ssl', true ) );

		// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
		$conn = $ssl && function_exists( 'ftp_ssl_connect' ) ? @ftp_ssl_connect( $host, $port, 20 ) : @ftp_connect( $host, $port, 20 );
		if ( ! $conn ) {
			return new \WP_Error( 'ftp_connect', __( 'Could not connect to the FTP server.', 'dicestack' ) );
		}
		if ( ! @ftp_login( $conn, (string) $this->get_setting( 'ftp_user', '' ), (string) $this->get_setting( 'ftp_pass', '' ) ) ) {
			@ftp_close( $conn );
			return new \WP_Error( 'ftp_login', __( 'FTP login failed.', 'dicestack' ) );
		}
		@ftp_pasv( $conn, true );
		$dir = trailingslashit( (string) $this->get_setting( 'ftp_dir', '/' ) );
		$ok  = @ftp_put( $conn, $dir . basename( $file ), $file, FTP_BINARY );
		@ftp_close( $conn );
		// phpcs:enable WordPress.PHP.NoSilencedErrors.Discouraged

		return $ok ? true : new \WP_Error( 'ftp_put', __( 'FTP upload failed (check the folder path and permissions).', 'dicestack' ) );
	}

	/**
	 * Upload via WebDAV (HTTP PUT with Basic auth).
	 *
	 * @param string $file File path.
	 * @return bool|\WP_Error
	 */
	private function upload_webdav( $file ) {
		$url = trailingslashit( (string) $this->get_setting( 'webdav_url', '' ) ) . rawurlencode( basename( $file ) );
		if ( '' === trim( (string) $this->get_setting( 'webdav_url', '' ) ) ) {
			return new \WP_Error( 'webdav_url', __( 'WebDAV URL is empty.', 'dicestack' ) );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading our own backup file to upload.
		$body = file_get_contents( $file );
		if ( false === $body ) {
			return new \WP_Error( 'read', __( 'Could not read the backup file.', 'dicestack' ) );
		}
		$auth     = base64_encode( $this->get_setting( 'webdav_user', '' ) . ':' . $this->get_setting( 'webdav_pass', '' ) );
		$response = wp_remote_request(
			$url,
			array(
				'method'  => 'PUT',
				'timeout' => 60,
				'headers' => array( 'Authorization' => 'Basic ' . $auth ),
				'body'    => $body,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		return ( $code >= 200 && $code < 300 ) ? true : new \WP_Error( 'webdav', __( 'WebDAV upload failed (HTTP ', 'dicestack' ) . $code . ').' );
	}

	/**
	 * Email the backup as an attachment.
	 *
	 * @param string $file File path.
	 * @return bool|\WP_Error
	 */
	private function upload_email( $file ) {
		// Guard against emailing very large files.
		if ( filesize( $file ) > 20 * 1024 * 1024 ) {
			return new \WP_Error( 'too_big', __( 'Backup is over 20 MB — too large to email. Use FTP, WebDAV, or Google Drive.', 'dicestack' ) );
		}
		$to = sanitize_email( (string) $this->get_setting( 'email_to', get_option( 'admin_email' ) ) );
		$ok = wp_mail(
			$to,
			'[' . get_bloginfo( 'name' ) . '] ' . __( 'Site backup', 'dicestack' ),
			__( 'Your latest DiceStack backup is attached.', 'dicestack' ),
			array(),
			array( $file )
		);
		return $ok ? true : new \WP_Error( 'mail', __( 'Email send failed.', 'dicestack' ) );
	}

	/**
	 * Upload to Google Drive using a stored access token (multipart upload).
	 *
	 * @param string $file File path.
	 * @return bool|\WP_Error
	 */
	private function upload_gdrive( $file ) {
		$token = trim( (string) $this->get_setting( 'gdrive_token', '' ) );
		if ( '' === $token ) {
			return new \WP_Error( 'no_token', __( 'Connect Google Drive first to get an access token.', 'dicestack' ) );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading our own backup file.
		$content  = file_get_contents( $file );
		if ( false === $content ) {
			return new \WP_Error( 'read', __( 'Could not read the backup file.', 'dicestack' ) );
		}
		$folder   = trim( (string) $this->get_setting( 'gdrive_folder', '' ) );
		$metadata = array( 'name' => basename( $file ) );
		if ( '' !== $folder ) {
			$metadata['parents'] = array( $folder );
		}

		$boundary = wp_generate_password( 24, false );
		$body     = "--{$boundary}\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n" . wp_json_encode( $metadata ) . "\r\n";
		$body    .= "--{$boundary}\r\nContent-Type: application/zip\r\n\r\n" . $content . "\r\n--{$boundary}--";

		$response = wp_remote_post(
			'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart',
			array(
				'timeout' => 90,
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'multipart/related; boundary=' . $boundary,
				),
				'body'    => $body,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( 401 === $code ) {
			return new \WP_Error( 'token_expired', __( 'Google token expired — reconnect Google Drive.', 'dicestack' ) );
		}
		return ( $code >= 200 && $code < 300 ) ? true : new \WP_Error( 'gdrive', __( 'Google Drive upload failed.', 'dicestack' ) );
	}

	/**
	 * Register the page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'dicestack',
			__( 'Cloud backup', 'dicestack' ),
			__( 'Cloud backup', 'dicestack' ),
			'manage_options',
			'dicestack-cloud',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Upload the most recent backup now (test).
	 *
	 * @return void
	 */
	public function handle_test() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dicestack_cloud_test' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}
		$uploads = wp_get_upload_dir();
		$files   = glob( trailingslashit( $uploads['basedir'] ) . 'dicestack-backups/dicestack-backup-*.zip' );
		if ( ! empty( $files ) ) {
			usort( $files, static function ( $a, $b ) { return filemtime( $b ) - filemtime( $a ); } );
			$this->upload( $files[0] );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=dicestack-cloud' ) );
		exit;
	}

	/**
	 * Render the cloud backup page.
	 *
	 * @return void
	 */
	public function render_page() {
		$last = get_option( 'dicestack_cloud_last', array() );
		// Round-trip a state nonce through the connector; verified on return.
		$return = add_query_arg( 'dicestack_state', wp_create_nonce( 'dicestack_gdrive_connect' ), admin_url( 'admin.php?page=dicestack-cloud' ) );
		$relay  = 'https://dicecodes.com/dicestack-wordpress-plugin/connect/google?return=' . rawurlencode( $return );

		echo '<div class="wrap"><h1>' . esc_html__( 'Cloud backup', 'dicestack' ) . '</h1>';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.
		if ( isset( $_GET['settings-saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'dicestack' ) . '</p></div>';
		}
		echo '<p>' . esc_html__( 'Choose where backups go and enter the connection details below. FTP, WebDAV, and Email need only your login — no API key.', 'dicestack' ) . '</p>';
		echo '<h2>' . esc_html__( 'Destination settings', 'dicestack' ) . '</h2>';
		echo \DiceStack\Admin\Settings_Renderer::page_form( $this ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped internally.

		if ( is_array( $last ) && ! empty( $last['time'] ) ) {
			$cls = ! empty( $last['ok'] ) ? 'notice-success' : 'notice-error';
			echo '<div class="notice ' . esc_attr( $cls ) . '"><p>' . esc_html( sprintf( /* translators: 1: status, 2: time. */ __( 'Last cloud upload: %1$s (%2$s ago)', 'dicestack' ), $last['message'], human_time_diff( (int) $last['time'], time() ) ) ) . '</p></div>';
		}

		echo '<h2>' . esc_html__( 'Google Drive', 'dicestack' ) . '</h2>';
		echo '<p>' . esc_html__( 'One-click connect (no API key) — authorises via the Dice Codes connector, then pastes your token back automatically:', 'dicestack' ) . '</p>';
		echo '<p><a class="button button-primary" href="' . esc_url( $relay ) . '">' . esc_html__( 'Connect Google Drive', 'dicestack' ) . '</a></p>';

		echo '<h2>' . esc_html__( 'Send latest backup now', 'dicestack' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'dicestack_cloud_test' );
		echo '<input type="hidden" name="action" value="dicestack_cloud_test" />';
		echo '<button type="submit" class="button">' . esc_html__( 'Upload latest backup to cloud', 'dicestack' ) . '</button>';
		echo '</form></div>';
	}
}
