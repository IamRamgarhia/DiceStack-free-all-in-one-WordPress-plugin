<?php
/**
 * Server capability detection.
 *
 * @package DiceStack
 */

namespace DiceStack;

defined( 'ABSPATH' ) || exit;

/**
 * Detects what the current hosting environment actually supports (Redis,
 * Memcached, image libraries, ZipArchive, OPcache, …) so modules that need a
 * capability can be hidden — with a clear "how to enable" hint — instead of
 * letting the user turn on something that can't run here.
 */
final class Environment {

	/**
	 * Capability cache.
	 *
	 * @var array<string,bool>|null
	 */
	private static $cache = null;

	/**
	 * The full capability map: key => [label, available, hint].
	 *
	 * @return array<string,array{label:string,available:bool,hint:string}>
	 */
	public static function map() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$redis     = class_exists( 'Redis' ) || class_exists( 'Predis\Client' );
		$memcached = class_exists( 'Memcached' ) || class_exists( 'Memcache' );
		$imagick   = extension_loaded( 'imagick' ) && class_exists( 'Imagick' );
		$gd        = extension_loaded( 'gd' ) && function_exists( 'imagecreatetruecolor' );

		$webp = ( $imagick && in_array( 'WEBP', self::imagick_formats(), true ) ) || ( $gd && function_exists( 'imagewebp' ) );
		$avif = ( $imagick && in_array( 'AVIF', self::imagick_formats(), true ) ) || ( $gd && function_exists( 'imageavif' ) );

		self::$cache = array(
			'object_cache_backend' => array(
				'label'     => __( 'Redis or Memcached', 'dicestack' ),
				'available' => $redis || $memcached,
				'hint'      => __( 'Ask your host to enable the Redis or Memcached PHP extension (most managed hosts offer it on request or in the control panel).', 'dicestack' ),
			),
			'redis'                => array(
				'label'     => __( 'Redis PHP extension', 'dicestack' ),
				'available' => $redis,
				'hint'      => __( 'Install the phpredis extension, or ask your host to enable Redis.', 'dicestack' ),
			),
			'memcached'            => array(
				'label'     => __( 'Memcached PHP extension', 'dicestack' ),
				'available' => $memcached,
				'hint'      => __( 'Install the Memcached extension, or ask your host to enable it.', 'dicestack' ),
			),
			'image_lib'            => array(
				'label'     => __( 'Imagick or GD image library', 'dicestack' ),
				'available' => $imagick || $gd,
				'hint'      => __( 'Ask your host to enable the Imagick (preferred) or GD PHP extension.', 'dicestack' ),
			),
			'imagick'              => array(
				'label'     => __( 'Imagick extension', 'dicestack' ),
				'available' => $imagick,
				'hint'      => __( 'Ask your host to enable the Imagick PHP extension.', 'dicestack' ),
			),
			'gd'                   => array(
				'label'     => __( 'GD image library', 'dicestack' ),
				'available' => $gd,
				'hint'      => __( 'Ask your host to enable the GD PHP extension.', 'dicestack' ),
			),
			'webp'                 => array(
				'label'     => __( 'WebP support', 'dicestack' ),
				'available' => $webp,
				'hint'      => __( 'WebP needs Imagick or GD built with WebP support. Ask your host to enable it.', 'dicestack' ),
			),
			'avif'                 => array(
				'label'     => __( 'AVIF support', 'dicestack' ),
				'available' => $avif,
				'hint'      => __( 'AVIF needs a recent Imagick or GD (PHP 8.1+). Ask your host to enable it.', 'dicestack' ),
			),
			'zip'                  => array(
				'label'     => __( 'ZipArchive extension', 'dicestack' ),
				'available' => class_exists( 'ZipArchive' ),
				'hint'      => __( 'Ask your host to enable the PHP zip extension (needed to create backup archives).', 'dicestack' ),
			),
			'exif'                 => array(
				'label'     => __( 'EXIF extension', 'dicestack' ),
				'available' => extension_loaded( 'exif' ),
				'hint'      => __( 'Ask your host to enable the PHP exif extension.', 'dicestack' ),
			),
			'curl'                 => array(
				'label'     => __( 'cURL extension', 'dicestack' ),
				'available' => function_exists( 'curl_init' ),
				'hint'      => __( 'Ask your host to enable the PHP cURL extension.', 'dicestack' ),
			),
			'opcache'              => array(
				'label'     => __( 'OPcache', 'dicestack' ),
				'available' => function_exists( 'opcache_get_status' ) || ( function_exists( 'ini_get' ) && ini_get( 'opcache.enable' ) ),
				'hint'      => __( 'Ask your host to enable PHP OPcache for faster execution.', 'dicestack' ),
			),
			'gzip'                 => array(
				'label'     => __( 'Gzip compression', 'dicestack' ),
				'available' => function_exists( 'gzencode' ),
				'hint'      => __( 'Ask your host to enable the PHP zlib extension.', 'dicestack' ),
			),
		);

		return self::$cache;
	}

	/**
	 * Imagick supported formats (cached, guarded).
	 *
	 * @return string[]
	 */
	private static function imagick_formats() {
		static $formats = null;
		if ( null === $formats ) {
			$formats = array();
			if ( class_exists( 'Imagick' ) ) {
				try {
					$formats = \Imagick::queryFormats();
				} catch ( \Exception $e ) {
					$formats = array();
				}
			}
		}
		return is_array( $formats ) ? $formats : array();
	}

	/**
	 * Is a single capability available?
	 *
	 * @param string $key Capability key.
	 * @return bool
	 */
	public static function has( $key ) {
		$map = self::map();
		return isset( $map[ $key ] ) ? (bool) $map[ $key ]['available'] : true; // Unknown key = don't block.
	}

	/**
	 * Whether a persistent external object cache is already active on the site.
	 *
	 * @return bool
	 */
	public static function object_cache_active() {
		return function_exists( 'wp_using_ext_object_cache' ) && wp_using_ext_object_cache();
	}

	/**
	 * Whether an object-cache.php drop-in is present in wp-content.
	 *
	 * @return bool
	 */
	public static function object_cache_dropin() {
		return file_exists( WP_CONTENT_DIR . '/object-cache.php' );
	}

	/**
	 * Detect other active plugins that already provide a feature area, so DiceStack
	 * never recommends turning on something that would clash (two SEO plugins,
	 * two page caches, etc.). Returns feature => "Plugin name".
	 *
	 * Feature keys: seo, page_cache, minify, object_cache, security, backup.
	 *
	 * @return array<string,string>
	 */
	public static function detected_plugins() {
		$found = array();

		// SEO.
		if ( defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Options' ) ) {
			$found['seo'] = 'Yoast SEO';
		} elseif ( class_exists( 'RankMath' ) || function_exists( 'rank_math' ) ) {
			$found['seo'] = 'Rank Math';
		} elseif ( defined( 'AIOSEO_VERSION' ) ) {
			$found['seo'] = 'All in One SEO';
		} elseif ( defined( 'SEOPRESS_VERSION' ) ) {
			$found['seo'] = 'SEOPress';
		} elseif ( defined( 'THE_SEO_FRAMEWORK_VERSION' ) ) {
			$found['seo'] = 'The SEO Framework';
		}

		// Page cache.
		if ( defined( 'WP_ROCKET_VERSION' ) ) {
			$found['page_cache'] = 'WP Rocket';
		} elseif ( defined( 'W3TC' ) || class_exists( 'W3_Plugin_TotalCache' ) ) {
			$found['page_cache'] = 'W3 Total Cache';
		} elseif ( defined( 'WPCACHEHOME' ) ) {
			$found['page_cache'] = 'WP Super Cache';
		} elseif ( defined( 'LSCWP_V' ) || class_exists( 'LiteSpeed\Core' ) ) {
			$found['page_cache'] = 'LiteSpeed Cache';
		} elseif ( class_exists( 'WpFastestCache' ) ) {
			$found['page_cache'] = 'WP Fastest Cache';
		} elseif ( defined( 'WPO_VERSION' ) || class_exists( 'WP_Optimize' ) ) {
			$found['page_cache'] = 'WP-Optimize';
		}

		// Minify / asset optimization.
		if ( class_exists( 'autoptimizeMain' ) || defined( 'AUTOPTIMIZE_PLUGIN_VERSION' ) ) {
			$found['minify'] = 'Autoptimize';
		} elseif ( defined( 'PERFMATTERS_VERSION' ) ) {
			$found['minify'] = 'Perfmatters';
		} elseif ( isset( $found['page_cache'] ) ) {
			$found['minify'] = $found['page_cache']; // WP Rocket / LiteSpeed also minify.
		}

		// Persistent object cache already active (any provider).
		if ( self::object_cache_active() ) {
			$found['object_cache'] = 'your host / another plugin';
		}

		// Security.
		if ( defined( 'WORDFENCE_VERSION' ) ) {
			$found['security'] = 'Wordfence';
		} elseif ( class_exists( 'ITSEC_Core' ) ) {
			$found['security'] = 'Solid Security';
		} elseif ( class_exists( 'SucuriScan' ) ) {
			$found['security'] = 'Sucuri';
		} elseif ( defined( 'AIOWPSEC_VERSION' ) ) {
			$found['security'] = 'All-In-One Security';
		}

		// Backup.
		if ( class_exists( 'UpdraftPlus' ) || defined( 'UPDRAFTPLUS_DIR' ) ) {
			$found['backup'] = 'UpdraftPlus';
		} elseif ( class_exists( 'BackWPup' ) ) {
			$found['backup'] = 'BackWPup';
		} elseif ( defined( 'DUPLICATOR_VERSION' ) ) {
			$found['backup'] = 'Duplicator';
		}

		return $found;
	}

	/**
	 * Map an DiceStack module id to the feature area it competes in (or '' if none).
	 * Used to avoid recommending a tool another plugin already handles.
	 *
	 * @param string $id Module id.
	 * @return string
	 */
	public static function module_feature( $id ) {
		$map = array(
			'meta_tags'          => 'seo',
			'schema_jsonld'      => 'seo',
			'breadcrumbs'        => 'seo',
			'faq_schema'         => 'seo',
			'page_cache'         => 'page_cache',
			'minify_html'        => 'minify',
			'minify_css'         => 'minify',
			'minify_js'          => 'minify',
			'object_cache'       => 'object_cache',
			'security_hardening' => 'security',
			'login_protection'   => 'security',
			'rename_login'       => 'security',
			'backup_restore'     => 'backup',
			'cloud_backup'       => 'backup',
		);
		return isset( $map[ $id ] ) ? $map[ $id ] : '';
	}

	/**
	 * Resolve the missing capabilities from a requirement list.
	 *
	 * @param string[] $required Capability keys.
	 * @return array<string,array{label:string,hint:string}> Missing ones.
	 */
	public static function missing( array $required ) {
		$map     = self::map();
		$missing = array();
		foreach ( $required as $key ) {
			if ( isset( $map[ $key ] ) && ! $map[ $key ]['available'] ) {
				$missing[ $key ] = array(
					'label' => $map[ $key ]['label'],
					'hint'  => $map[ $key ]['hint'],
				);
			}
		}
		return $missing;
	}
}
