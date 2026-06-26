<?php
/**
 * Backup & Restore module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Site;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Creates downloadable site backups (database + chosen folders) as a single zip,
 * on demand or on a schedule, with automatic retention cleanup. Restore reads an
 * archive already sitting in the backups folder, so it never hits the PHP upload
 * limit (drop big archives in via FTP or the File Manager, then click Restore).
 *
 * Note: very large sites may need server-level tools; this suits typical sites.
 */
final class Backup_Restore extends Abstract_Module {

	/**
	 * Cron hook.
	 */
	const CRON_HOOK = 'dicestack_scheduled_backup';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'backup_restore';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Backup & restore', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Back up the database and files to a zip, schedule daily backups, and restore — no upload limit.', 'dicestack' );
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
		return 'database';
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
	public function performance_profile() {
		return array(
			'php_memory_kb' => 60,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
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
				'key'     => 'schedule',
				'label'   => __( 'Automatic backups', 'dicestack' ),
				'type'    => 'select',
				'default' => 'off',
				'options' => array(
					'off'    => __( 'Off (manual only)', 'dicestack' ),
					'daily'  => __( 'Daily', 'dicestack' ),
					'weekly' => __( 'Weekly', 'dicestack' ),
				),
			),
			array(
				'key'     => 'retention_days',
				'label'   => __( 'Delete backups older than (days)', 'dicestack' ),
				'type'    => 'number',
				'default' => 7,
				'min'     => 1,
				'max'     => 365,
				'step'    => 1,
			),
			array(
				'key'     => 'include_uploads',
				'label'   => __( 'Include the uploads folder (media)', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'include_plugins',
				'label'   => __( 'Include plugins folder', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'include_themes',
				'label'   => __( 'Include themes folder', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
		);
	}

	/**
	 * Backups directory (created + protected).
	 *
	 * @return string
	 */
	private function dir() {
		$uploads = wp_get_upload_dir();
		$dir     = trailingslashit( $uploads['basedir'] ) . 'dicestack-backups/';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
			// phpcs:disable WordPress.WP.AlternativeFunctions
			@file_put_contents( $dir . 'index.html', '' );
			@file_put_contents( $dir . '.htaccess', "Deny from all\n" );
			// phpcs:enable WordPress.WP.AlternativeFunctions
		}
		return $dir;
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_action( self::CRON_HOOK, array( $this, 'run_backup' ) );
		add_action( 'init', array( $this, 'ensure_schedule' ) );
		add_action( 'dicestack_module_disabled_' . $this->id(), array( $this, 'clear_schedule' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_page' ) );
			add_action( 'admin_post_dicestack_backup_now', array( $this, 'handle_now' ) );
			add_action( 'admin_post_dicestack_backup_delete', array( $this, 'handle_delete' ) );
			add_action( 'admin_post_dicestack_backup_restore', array( $this, 'handle_restore' ) );
			add_action( 'admin_post_dicestack_backup_download', array( $this, 'handle_download' ) );
		}
	}

	/**
	 * Keep the cron event aligned with the chosen schedule.
	 *
	 * @return void
	 */
	public function ensure_schedule() {
		$schedule  = $this->get_setting( 'schedule', 'off' );
		$scheduled = wp_next_scheduled( self::CRON_HOOK );
		if ( 'off' === $schedule ) {
			if ( $scheduled ) {
				wp_unschedule_event( $scheduled, self::CRON_HOOK );
			}
			return;
		}
		if ( ! $scheduled ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, $schedule, self::CRON_HOOK );
		}
	}

	/**
	 * Remove the cron event.
	 *
	 * @return void
	 */
	public function clear_schedule() {
		$scheduled = wp_next_scheduled( self::CRON_HOOK );
		if ( $scheduled ) {
			wp_unschedule_event( $scheduled, self::CRON_HOOK );
		}
	}

	/**
	 * Export the database to a .sql file.
	 *
	 * @param string $file Destination path.
	 * @return bool
	 */
	private function export_database( $file ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.WP.AlternativeFunctions
		$handle = @fopen( $file, 'w' );
		if ( ! $handle ) {
			return false;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$tables = $wpdb->get_col( 'SHOW TABLES' );
		foreach ( (array) $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table comes from SHOW TABLES (trusted); identifiers cannot be bound via prepare().
			$create = $wpdb->get_row( "SHOW CREATE TABLE `{$table}`", ARRAY_N );
			if ( isset( $create[1] ) ) {
				fwrite( $handle, "DROP TABLE IF EXISTS `{$table}`;\n" . $create[1] . ";\n\n" );
			}
			$offset = 0;
			do {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table from SHOW TABLES (trusted); LIMIT/OFFSET are bound.
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` LIMIT %d OFFSET %d", 500, $offset ), ARRAY_A );
				foreach ( (array) $rows as $row ) {
					$values = array_map(
						static function ( $v ) {
							if ( null === $v ) {
								return 'NULL';
							}
							// Escape for SQL, then keep the value on a single physical
							// line so the line-based importer can never mis-split on a
							// literal newline+semicolon inside the data.
							$escaped = esc_sql( (string) $v );
							$escaped = str_replace( array( "\r", "\n" ), array( '\\r', '\\n' ), $escaped );
							return "'" . $escaped . "'";
						},
						array_values( $row )
					);
					fwrite( $handle, "INSERT INTO `{$table}` VALUES (" . implode( ',', $values ) . ");\n" );
				}
				$offset += 500;
			} while ( ! empty( $rows ) );
			fwrite( $handle, "\n" );
		}
		fclose( $handle );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.WP.AlternativeFunctions
		return true;
	}

	/**
	 * Create a backup archive. Returns the filename or WP_Error.
	 *
	 * @return string|\WP_Error
	 */
	public function run_backup() {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new \WP_Error( 'no_zip', __( 'PHP ZipArchive is not available on this server.', 'dicestack' ) );
		}
		@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Squiz.PHP.DiscouragedFunctions.Discouraged -- backups can take a while.

		$dir      = $this->dir();
		// Include a random token so archives can't be guessed/downloaded directly
		// even on servers where the .htaccess deny rule isn't honoured (Nginx).
		$stamp    = gmdate( 'Ymd-His' ) . '-' . wp_generate_password( 12, false );
		$sql_file = $dir . 'dicestack-db-' . $stamp . '.sql';
		$zip_file = $dir . 'dicestack-backup-' . $stamp . '.zip';

		if ( ! $this->export_database( $sql_file ) ) {
			return new \WP_Error( 'db_fail', __( 'Could not export the database.', 'dicestack' ) );
		}

		$zip = new \ZipArchive();
		if ( true !== $zip->open( $zip_file, \ZipArchive::CREATE ) ) {
			return new \WP_Error( 'zip_fail', __( 'Could not create the zip archive.', 'dicestack' ) );
		}
		$zip->addFile( $sql_file, 'database.sql' );

		$content = WP_CONTENT_DIR;
		$folders = array();
		if ( ! empty( $this->get_setting( 'include_uploads', true ) ) ) {
			$up        = wp_get_upload_dir();
			$folders[] = $up['basedir'];
		}
		if ( ! empty( $this->get_setting( 'include_plugins', true ) ) ) {
			$folders[] = $content . '/plugins';
		}
		if ( ! empty( $this->get_setting( 'include_themes', true ) ) ) {
			$folders[] = $content . '/themes';
		}

		foreach ( $folders as $folder ) {
			$this->add_folder_to_zip( $zip, $folder, $content, $dir );
		}

		$zip->close();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		@unlink( $sql_file );

		$this->cleanup_old();
		update_option( 'dicestack_last_backup', time(), false );

		/**
		 * Fires after a backup archive is created. The Cloud Backup module uses
		 * this to upload the archive to a remote destination.
		 *
		 * @param string $zip_file Absolute path to the new backup zip.
		 */
		do_action( 'dicestack_backup_created', $zip_file );

		return basename( $zip_file );
	}

	/**
	 * Recursively add a folder to the zip (skipping the backups dir + caches).
	 *
	 * @param \ZipArchive $zip        Archive.
	 * @param string      $folder     Folder to add.
	 * @param string      $base       Base path to make entries relative to.
	 * @param string      $backup_dir Backups dir to skip.
	 * @return void
	 */
	private function add_folder_to_zip( $zip, $folder, $base, $backup_dir ) {
		if ( ! is_dir( $folder ) ) {
			return;
		}
		$base = wp_normalize_path( $base );
		$iter = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $folder, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::SELF_FIRST
		);
		foreach ( $iter as $item ) {
			$path = wp_normalize_path( $item->getPathname() );
			if ( false !== strpos( $path, wp_normalize_path( $backup_dir ) ) || false !== strpos( $path, '/dicestack-cache/' ) || false !== strpos( $path, '/cache/' ) ) {
				continue;
			}
			$relative = 'wp-content/' . ltrim( str_replace( $base, '', $path ), '/' );
			if ( $item->isDir() ) {
				$zip->addEmptyDir( $relative );
			} else {
				$zip->addFile( $path, $relative );
			}
		}
	}

	/**
	 * Delete backups beyond the retention window.
	 *
	 * @return void
	 */
	private function cleanup_old() {
		$days = (int) $this->get_setting( 'retention_days', 7 );
		$cut  = time() - ( $days * DAY_IN_SECONDS );
		foreach ( (array) glob( $this->dir() . 'dicestack-backup-*.zip' ) as $file ) {
			if ( filemtime( $file ) < $cut ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				@unlink( $file );
			}
		}
	}

	/**
	 * List existing backups.
	 *
	 * @return array[]
	 */
	private function list_backups() {
		$out = array();
		foreach ( (array) glob( $this->dir() . 'dicestack-backup-*.zip' ) as $file ) {
			$out[] = array(
				'name' => basename( $file ),
				'size' => size_format( (int) filesize( $file ) ),
				'time' => filemtime( $file ),
			);
		}
		usort( $out, static function ( $a, $b ) { return $b['time'] - $a['time']; } );
		return $out;
	}

	/* ----- Admin ---------------------------------------------------------- */

	/**
	 * Register the page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'dicestack',
			__( 'Backups', 'dicestack' ),
			__( 'Backups', 'dicestack' ),
			'manage_options',
			'dicestack-backups',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Validate an admin action request.
	 *
	 * @param string $nonce Nonce action.
	 * @return void
	 */
	private function guard( $nonce ) {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $nonce ) ) {
			wp_die( esc_html__( 'Permission denied.', 'dicestack' ) );
		}
	}

	/**
	 * Handle "Backup now".
	 *
	 * @return void
	 */
	public function handle_now() {
		$this->guard( 'dicestack_backup_now' );
		$result = $this->run_backup();
		$flag   = is_wp_error( $result ) ? 'error' : 'ok';
		wp_safe_redirect( admin_url( 'admin.php?page=dicestack-backups&dicestack_b=' . $flag ) );
		exit;
	}

	/**
	 * Handle delete.
	 *
	 * @return void
	 */
	public function handle_delete() {
		$this->guard( 'dicestack_backup_delete' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified in guard().
		$name = isset( $_GET['file'] ) ? basename( sanitize_file_name( wp_unslash( $_GET['file'] ) ) ) : '';
		$path = $this->dir() . $name;
		if ( $name && is_file( $path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $path );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=dicestack-backups' ) );
		exit;
	}

	/**
	 * Stream a backup download.
	 *
	 * @return void
	 */
	public function handle_download() {
		$this->guard( 'dicestack_backup_download' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified in guard().
		$name = isset( $_GET['file'] ) ? basename( sanitize_file_name( wp_unslash( $_GET['file'] ) ) ) : '';
		$path = $this->dir() . $name;
		if ( ! $name || ! is_file( $path ) ) {
			wp_die( esc_html__( 'Backup not found.', 'dicestack' ) );
		}
		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename=' . $name );
		header( 'Content-Length: ' . filesize( $path ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $path );
		exit;
	}

	/**
	 * Restore from a backup already in the backups folder.
	 *
	 * @return void
	 */
	public function handle_restore() {
		$this->guard( 'dicestack_backup_restore' );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in guard().
		$name = isset( $_POST['file'] ) ? basename( sanitize_file_name( wp_unslash( $_POST['file'] ) ) ) : '';
		$path = $this->dir() . $name;
		if ( ! $name || ! is_file( $path ) || ! class_exists( 'ZipArchive' ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=dicestack-backups&dicestack_b=error' ) );
			exit;
		}
		@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Squiz.PHP.DiscouragedFunctions.Discouraged -- backups can take a while.

		$tmp = $this->dir() . 'restore-tmp/';
		$zip = new \ZipArchive();
		if ( true !== $zip->open( $path ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=dicestack-backups&dicestack_b=error' ) );
			exit;
		}

		// Zip Slip guard: reject any entry that tries to escape the temp dir.
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$entry = $zip->getNameIndex( $i );
			if ( false === $entry ) {
				continue;
			}
			$entry = str_replace( '\\', '/', $entry );
			if ( 0 === strpos( $entry, '/' ) || false !== strpos( $entry, '../' ) || preg_match( '#^[A-Za-z]:#', $entry ) ) {
				$zip->close();
				wp_safe_redirect( admin_url( 'admin.php?page=dicestack-backups&dicestack_b=error' ) );
				exit;
			}
		}

		$zip->extractTo( $tmp );
		$zip->close();

		// Restore files.
		if ( is_dir( $tmp . 'wp-content' ) ) {
			$this->copy_tree( $tmp . 'wp-content', WP_CONTENT_DIR );
		}
		// Restore database.
		if ( is_file( $tmp . 'database.sql' ) ) {
			$this->import_sql( $tmp . 'database.sql' );
		}
		$this->delete_tree( $tmp );

		wp_safe_redirect( admin_url( 'admin.php?page=dicestack-backups&dicestack_b=restored' ) );
		exit;
	}

	/**
	 * Import an SQL dump statement-by-statement.
	 *
	 * @param string $file SQL file path.
	 * @return void
	 */
	private function import_sql( $file ) {
		global $wpdb;
		// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$handle = @fopen( $file, 'r' );
		if ( ! $handle ) {
			return;
		}
		$buffer = '';
		while ( false !== ( $line = fgets( $handle ) ) ) {
			$trim = ltrim( $line );
			if ( '' === trim( $line ) || 0 === strpos( $trim, '--' ) || 0 === strpos( $trim, '#' ) ) {
				continue;
			}
			$buffer .= $line;
			if ( ';' === substr( rtrim( $line ), -1 ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- restoring a trusted admin-uploaded SQL dump; full statements cannot be prepared.
				$wpdb->query( $buffer );
				$buffer = '';
			}
		}
		fclose( $handle );
		// phpcs:enable WordPress.WP.AlternativeFunctions, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Recursively copy a directory tree.
	 *
	 * @param string $src Source.
	 * @param string $dst Destination.
	 * @return void
	 */
	private function copy_tree( $src, $dst ) {
		$iter = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $src, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::SELF_FIRST
		);
		foreach ( $iter as $item ) {
			$target = $dst . '/' . $iter->getSubPathName();
			if ( $item->isDir() ) {
				if ( ! is_dir( $target ) ) {
					wp_mkdir_p( $target );
				}
			} else {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.copy_copy
				@copy( $item->getPathname(), $target );
			}
		}
	}

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $dir Directory.
	 * @return void
	 */
	private function delete_tree( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$iter = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $iter as $item ) {
			// phpcs:disable WordPress.WP.AlternativeFunctions
			if ( $item->isDir() ) {
				@rmdir( $item->getPathname() );
			} else {
				@unlink( $item->getPathname() );
			}
			// phpcs:enable WordPress.WP.AlternativeFunctions
		}
		@rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.directory_rmdir, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- removing our own temp extraction dir.
	}

	/**
	 * Render the backups page.
	 *
	 * @return void
	 */
	public function render_page() {
		$flag = isset( $_GET['dicestack_b'] ) ? sanitize_text_field( wp_unslash( $_GET['dicestack_b'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="wrap"><h1>' . esc_html__( 'Backups', 'dicestack' ) . '</h1>';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.
		if ( isset( $_GET['settings-saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'dicestack' ) . '</p></div>';
		}

		if ( 'ok' === $flag ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Backup created.', 'dicestack' ) . '</p></div>';
		} elseif ( 'restored' === $flag ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Restore complete.', 'dicestack' ) . '</p></div>';
		} elseif ( 'error' === $flag ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Operation failed. Check that PHP ZipArchive is available and the server has enough resources.', 'dicestack' ) . '</p></div>';
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:16px 0;">';
		wp_nonce_field( 'dicestack_backup_now' );
		echo '<input type="hidden" name="action" value="dicestack_backup_now" />';
		echo '<button type="submit" class="button button-primary">' . esc_html__( 'Back up now', 'dicestack' ) . '</button>';
		echo '</form>';

		$settings_form = \DiceStack\Admin\Settings_Renderer::page_form( $this );
		if ( '' !== $settings_form ) {
			echo '<h2>' . esc_html__( 'Schedule & options', 'dicestack' ) . '</h2>';
			echo $settings_form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped internally.
		}

		$backups = $this->list_backups();
		echo '<h2>' . esc_html__( 'Available backups', 'dicestack' ) . '</h2>';
		if ( empty( $backups ) ) {
			echo '<p>' . esc_html__( 'No backups yet.', 'dicestack' ) . '</p>';
		} else {
			echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Backup', 'dicestack' ) . '</th><th>' . esc_html__( 'Size', 'dicestack' ) . '</th><th>' . esc_html__( 'When', 'dicestack' ) . '</th><th>' . esc_html__( 'Actions', 'dicestack' ) . '</th></tr></thead><tbody>';
			foreach ( $backups as $b ) {
				$dl  = wp_nonce_url( admin_url( 'admin-post.php?action=dicestack_backup_download&file=' . rawurlencode( $b['name'] ) ), 'dicestack_backup_download' );
				$del = wp_nonce_url( admin_url( 'admin-post.php?action=dicestack_backup_delete&file=' . rawurlencode( $b['name'] ) ), 'dicestack_backup_delete' );
				echo '<tr><td><code>' . esc_html( $b['name'] ) . '</code></td><td>' . esc_html( $b['size'] ) . '</td><td>' . esc_html( gmdate( 'Y-m-d H:i', $b['time'] ) ) . '</td><td>';
				echo '<a class="button" href="' . esc_url( $dl ) . '">' . esc_html__( 'Download', 'dicestack' ) . '</a> ';
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline" onsubmit="return confirm(\'' . esc_js( __( 'Restore this backup? This overwrites current files and database.', 'dicestack' ) ) . '\');">';
				wp_nonce_field( 'dicestack_backup_restore' );
				echo '<input type="hidden" name="action" value="dicestack_backup_restore" /><input type="hidden" name="file" value="' . esc_attr( $b['name'] ) . '" />';
				echo '<button type="submit" class="button">' . esc_html__( 'Restore', 'dicestack' ) . '</button></form> ';
				echo '<a class="button-link-delete" href="' . esc_url( $del ) . '">' . esc_html__( 'Delete', 'dicestack' ) . '</a>';
				echo '</td></tr>';
			}
			echo '</tbody></table>';
		}
		echo '<p style="color:#6b7280;">' . esc_html__( 'Tip: to migrate to a new site, install DiceStack there, drop the .zip into wp-content/uploads/dicestack-backups via FTP, then click Restore — no upload limit.', 'dicestack' ) . '</p>';
		echo '</div>';
	}
}
