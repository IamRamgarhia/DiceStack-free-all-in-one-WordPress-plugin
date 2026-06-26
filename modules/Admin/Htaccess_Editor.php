<?php
/**
 * .htaccess editor module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Edit the site's root .htaccess from the same inline settings modal as every
 * other tool. Each save automatically keeps a backup of the previous version in
 * the database. Administrators only, and it refuses to write when
 * DISALLOW_FILE_MODS/EDIT is set.
 */
final class Htaccess_Editor extends Abstract_Module {

	const BACKUP_OPTION = 'dicestack_htaccess_backup';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'htaccess_editor';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( '.htaccess editor', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Edit your root .htaccess from the dashboard with an automatic backup on every save.', 'dicestack' );
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
	public function performance_profile() {
		return array(
			'php_memory_kb' => 12,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 0,
			'external_http' => 0,
		);
	}

	/**
	 * Path to the root .htaccess.
	 *
	 * @return string
	 */
	private function file() {
		return ABSPATH . '.htaccess';
	}

	/**
	 * Whether file edits are blocked by WordPress constants.
	 *
	 * @return bool
	 */
	private function blocked() {
		return ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) || ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT );
	}

	/**
	 * {@inheritDoc}
	 */
	public function settings_schema() {
		$current = '';
		$file    = $this->file();
		if ( is_readable( $file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading the site's own .htaccess for the editor.
			$current = (string) file_get_contents( $file );
		}
		return array(
			array(
				'key'     => 'content',
				'label'   => __( 'Root .htaccess', 'dicestack' ),
				'type'    => 'textarea',
				'default' => $current,
				'help'    => __( 'Warning: a broken .htaccess can take your site offline. A backup of the previous version is saved automatically on each save. If you ever lock yourself out, restore it via FTP / your host file manager.', 'dicestack' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		// Nothing to hook on the front end; the file is written on save.
	}

	/**
	 * Save handler: back up the current .htaccess, then write the new content.
	 * Overrides the schema saver because we write to a file, not just an option.
	 *
	 * @param array $input Raw posted values.
	 * @return array
	 */
	public function save_settings( array $input ) {
		if ( ! current_user_can( 'manage_options' ) || $this->blocked() ) {
			return array();
		}
		$content = isset( $input['content'] ) ? (string) wp_unslash( $input['content'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- raw server config; sanitising would corrupt directives.
		$file    = $this->file();

		if ( is_readable( $file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			update_option( self::BACKUP_OPTION, (string) file_get_contents( $file ), false );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		@file_put_contents( $file, $content, LOCK_EX );

		// Don't persist the content as an option — the field always reads the live
		// file via settings_schema() default, so it stays in sync.
		return array();
	}
}
