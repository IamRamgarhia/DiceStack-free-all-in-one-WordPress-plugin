<?php
/**
 * Sanitize Filenames module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Media;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Cleans uploaded filenames — lowercase, hyphens instead of spaces, accents
 * stripped — for tidy, URL-safe, SEO-friendly media URLs.
 */
final class Sanitize_Filenames extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'sanitize_filenames';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'Tidy upload filenames', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Make uploaded filenames lowercase and URL-safe (spaces and accents removed).', 'dicestack' );
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
	public function init() {
		add_filter( 'sanitize_file_name', array( $this, 'clean' ), 10 );
	}

	/**
	 * Clean the filename.
	 *
	 * @param string $filename Proposed filename.
	 * @return string
	 */
	public function clean( $filename ) {
		$info = pathinfo( $filename );
		$ext  = isset( $info['extension'] ) ? '.' . strtolower( $info['extension'] ) : '';
		$name = isset( $info['filename'] ) ? $info['filename'] : $filename;

		$name = remove_accents( $name );
		$name = sanitize_title( $name );

		return ( '' !== $name ? $name : 'file' ) . $ext;
	}
}
