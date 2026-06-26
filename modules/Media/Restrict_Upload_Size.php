<?php
/**
 * Restrict Upload Size module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Media;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Caps the maximum upload file size for non-administrators, stopping editors
 * from filling the server with huge files.
 */
final class Restrict_Upload_Size extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'restrict_upload_size';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Limit upload size', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Cap how large a file non-admins can upload to keep the media library tidy.', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category() {
		return 'media';
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return 'photo';
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
	 * {@inheritDoc}
	 */
	public function settings_schema() {
		return array(
			array(
				'key'     => 'max_mb',
				'label'   => __( 'Maximum upload size (MB)', 'dicestack' ),
				'type'    => 'number',
				'default' => 5,
				'min'     => 1,
				'max'     => 512,
				'step'    => 1,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'check' ) );
	}

	/**
	 * Reject oversized uploads from non-admins.
	 *
	 * @param array $file Upload array.
	 * @return array
	 */
	public function check( $file ) {
		if ( current_user_can( 'manage_options' ) ) {
			return $file;
		}
		$max_bytes = (int) $this->get_setting( 'max_mb', 5 ) * 1024 * 1024;
		if ( isset( $file['size'] ) && $file['size'] > $max_bytes ) {
			$file['error'] = sprintf(
				/* translators: %d: max size in MB. */
				__( 'File is too large. Maximum allowed size is %d MB.', 'dicestack' ),
				(int) $this->get_setting( 'max_mb', 5 )
			);
		}
		return $file;
	}
}
