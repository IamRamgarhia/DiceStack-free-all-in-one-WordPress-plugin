<?php
/**
 * SVG Upload module.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules\Admin;

use DiceStack\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Allows SVG uploads with a sanitisation pass that strips scripts and event
 * handlers. Replaces Safe SVG. Only users who can already upload files benefit.
 */
final class SVG_Upload extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'svg_upload';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name() {
		return __( 'SVG upload', 'dicestack' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Safely allow SVG uploads with automatic sanitisation of dangerous code.', 'dicestack' );
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
		return 'photo';
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaces() {
		return 'premium SVG plugins';
	}

	/**
	 * {@inheritDoc}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 45,
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
		add_filter( 'upload_mimes', array( $this, 'allow_svg' ) );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'sanitize_on_upload' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'fix_filetype_check' ), 10, 4 );
	}

	/**
	 * Add SVG to allowed mime types (admins/editors only).
	 *
	 * @param array $mimes Allowed mimes.
	 * @return array
	 */
	public function allow_svg( $mimes ) {
		if ( current_user_can( 'manage_options' ) || current_user_can( 'upload_files' ) ) {
			$mimes['svg']  = 'image/svg+xml';
			$mimes['svgz'] = 'image/svg+xml';
		}
		return $mimes;
	}

	/**
	 * Let WordPress accept the SVG mime during its filetype check.
	 *
	 * @param array  $data     File data.
	 * @param string $file     File path.
	 * @param string $filename File name.
	 * @param array  $mimes    Allowed mimes.
	 * @return array
	 */
	public function fix_filetype_check( $data, $file, $filename, $mimes ) {
		if ( substr( strtolower( $filename ), -4 ) === '.svg' ) {
			$data['ext']  = 'svg';
			$data['type'] = 'image/svg+xml';
		}
		return $data;
	}

	/**
	 * Sanitise an SVG file before it is stored.
	 *
	 * @param array $file Upload array (name, type, tmp_name, ...).
	 * @return array
	 */
	public function sanitize_on_upload( $file ) {
		// Decide by extension, not MIME — a .svgz or a spoofed MIME must not slip past.
		$name = isset( $file['name'] ) ? strtolower( $file['name'] ) : '';
		$ext  = substr( $name, -4 );
		$is_svg  = ( '.svg' === $ext );
		$is_svgz = ( '.svgz' === substr( $name, -5 ) );
		if ( ! $is_svg && ! $is_svgz ) {
			return $file;
		}

		$contents = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a just-uploaded temp file.
		if ( false === $contents ) {
			$file['error'] = __( 'Could not read the SVG file.', 'dicestack' );
			return $file;
		}

		// SVGZ is gzip-compressed — decompress, sanitise, recompress.
		if ( $is_svgz ) {
			$decoded = function_exists( 'gzdecode' ) ? @gzdecode( $contents ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false === $decoded ) {
				$file['error'] = __( 'This SVGZ could not be read and was blocked.', 'dicestack' );
				return $file;
			}
			$contents = $decoded;
		}

		$clean = $this->sanitize_svg( $contents );
		if ( null === $clean ) {
			$file['error'] = __( 'This SVG could not be safely sanitised and was blocked.', 'dicestack' );
			return $file;
		}

		if ( $is_svgz && function_exists( 'gzencode' ) ) {
			$clean = gzencode( $clean );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- writing back the sanitised temp file.
		file_put_contents( $file['tmp_name'], $clean );
		return $file;
	}

	/**
	 * Strip scripts, event handlers, and external references from SVG markup.
	 *
	 * @param string $svg Raw SVG.
	 * @return string|null Sanitised SVG, or null if it can't be parsed.
	 */
	private function sanitize_svg( $svg ) {
		if ( '' === trim( $svg ) ) {
			return null;
		}

		// Remove anything before the first <svg and after the last </svg>.
		$start = stripos( $svg, '<svg' );
		$end   = strripos( $svg, '</svg>' );
		if ( false === $start || false === $end ) {
			return null;
		}
		$svg = substr( $svg, $start, ( $end - $start ) + 6 );

		// Remove DOCTYPE / ENTITY (XXE) and processing instructions / CDATA.
		$svg = preg_replace( '/<!DOCTYPE[^>]*>/is', '', $svg );
		$svg = preg_replace( '/<!ENTITY[^>]*>/is', '', $svg );
		$svg = preg_replace( '/<\?[^>]*\?>/s', '', $svg );
		$svg = preg_replace( '/<!\[CDATA\[.*?\]\]>/is', '', $svg );

		// Drop dangerous elements entirely (open+close and self-closing).
		$dangerous = array( 'script', 'foreignObject', 'iframe', 'embed', 'object', 'audio', 'video', 'animate', 'set', 'handler', 'listener' );
		foreach ( $dangerous as $tag ) {
			$svg = preg_replace( '#<' . $tag . '\b[^>]*>.*?</' . $tag . '>#is', '', $svg );
			$svg = preg_replace( '#<' . $tag . '\b[^>]*/?>#is', '', $svg );
		}

		// Drop ALL inline event handlers, quoted or unquoted (onload, onbegin, …).
		$svg = preg_replace( '/\son\w+\s*=\s*"[^"]*"/i', '', $svg );
		$svg = preg_replace( "/\son\w+\s*=\s*'[^']*'/i", '', $svg );
		$svg = preg_replace( '/\son\w+\s*=\s*[^\s>]+/i', '', $svg );

		// Neutralise javascript:/data: URLs in any href/src/attribute, quoted or not.
		$svg = preg_replace( '/(href|xlink:href|src|from|to|values|begin)\s*=\s*("|\')\s*(javascript|data)\s*:[^"\']*("|\')/i', '$1="#"', $svg );
		$svg = preg_replace( '/(href|xlink:href|src)\s*=\s*(javascript|data)\s*:[^\s>]+/i', '$1="#"', $svg );

		// Strip style attributes/elements that could carry url(javascript:…).
		$svg = preg_replace( '/\sstyle\s*=\s*"[^"]*expression[^"]*"/i', '', $svg );

		return $svg;
	}
}
