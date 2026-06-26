<?php
/**
 * Allow Extra File Types module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Media;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Enables uploading next-gen and common file types WordPress blocks by default
 * (WebP, AVIF, and a few document/font types) for admins.
 */
final class Allow_File_Types extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'allow_file_types';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Allow extra file types', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Permit WebP, AVIF, and other useful upload types that WordPress blocks by default.', 'dicestack' );
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
			'php_memory_kb' => 14,
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
				'key'     => 'webp',
				'label'   => __( 'Allow WebP', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'avif',
				'label'   => __( 'Allow AVIF', 'dicestack' ),
				'type'    => 'toggle',
				'default' => true,
			),
			array(
				'key'     => 'fonts',
				'label'   => __( 'Allow web fonts (woff, woff2)', 'dicestack' ),
				'type'    => 'toggle',
				'default' => false,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function init() {
		add_filter( 'upload_mimes', array( $this, 'add_mimes' ) );
	}

	/**
	 * Add the selected mime types for capable users.
	 *
	 * @param array $mimes Allowed mimes.
	 * @return array
	 */
	public function add_mimes( $mimes ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return $mimes;
		}
		if ( ! empty( $this->get_setting( 'webp', true ) ) ) {
			$mimes['webp'] = 'image/webp';
		}
		if ( ! empty( $this->get_setting( 'avif', true ) ) ) {
			$mimes['avif'] = 'image/avif';
		}
		if ( ! empty( $this->get_setting( 'fonts', false ) ) ) {
			$mimes['woff']  = 'font/woff';
			$mimes['woff2'] = 'font/woff2';
		}
		return $mimes;
	}
}
