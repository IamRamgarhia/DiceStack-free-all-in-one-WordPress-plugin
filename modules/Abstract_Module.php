<?php
/**
 * Base class for all DiceStack modules.
 *
 * @package DiceStack
 */

namespace DiceStack\Modules;

defined( 'ABSPATH' ) || exit;

/**
 * Every module extends this. It standardises identity, settings storage,
 * settings-form rendering, and the self-reported performance profile that
 * powers the transparency dashboard.
 */
abstract class Abstract_Module {

	/**
	 * Cached settings for this module.
	 *
	 * @var array|null
	 */
	private $settings_cache = null;

	/*
	 * -----------------------------------------------------------------------
	 * Identity — every module must implement these.
	 * -----------------------------------------------------------------------
	 */

	/**
	 * Unique, stable module ID (snake_case). Used as the option suffix and
	 * the key in the active-modules list. NEVER change once shipped.
	 *
	 * @return string
	 */
	abstract public function id();

	/**
	 * Human-readable module name.
	 *
	 * @return string
	 */
	abstract public function name();

	/**
	 * One-line description shown on the module card.
	 *
	 * @return string
	 */
	abstract public function description();

	/**
	 * Category slug (matches a key in Module_Registry::categories()).
	 *
	 * @return string
	 */
	abstract public function category();

	/**
	 * Register this module's hooks. Called only when the module is active.
	 *
	 * @return void
	 */
	abstract public function init();

	/*
	 * -----------------------------------------------------------------------
	 * Optional overrides.
	 * -----------------------------------------------------------------------
	 */

	/**
	 * Tabler icon name (without the "ti ti-" prefix) for the card.
	 *
	 * @return string
	 */
	public function icon() {
		return 'puzzle';
	}

	/**
	 * Premium plugin this module replaces, shown as a badge. Empty = none.
	 *
	 * @return string
	 */
	public function replaces() {
		return '';
	}

	/**
	 * Plugin slugs/constants this module depends on. Supported tokens:
	 * 'woocommerce'. Empty array = no dependency.
	 *
	 * @return string[]
	 */
	public function dependencies() {
		return array();
	}

	/**
	 * Server capabilities this module needs (keys from DiceStack\Environment).
	 * The dashboard hides the toggle and explains how to enable any missing
	 * capability instead of letting the user turn on a module that can't run.
	 * Empty array = runs anywhere. Supported keys: object_cache_backend, redis,
	 * memcached, image_lib, imagick, gd, webp, avif, zip, exif, opcache, curl.
	 *
	 * @return string[]
	 */
	public function requirements() {
		return array();
	}

	/**
	 * Does this module call an external service? If so the dashboard shows a
	 * consent notice before it can be enabled (WordPress.org privacy guideline).
	 *
	 * @return array{service:string,url:string,data:string}|null
	 */
	public function external_service() {
		return null;
	}

	/**
	 * Self-reported performance profile. Values are measured during development
	 * and surfaced on the dashboard so users can decide what to disable.
	 *
	 * @return array{php_memory_kb:int,front_js_kb:float,front_css_kb:float,db_queries:int,external_http:int}
	 */
	public function performance_profile() {
		return array(
			'php_memory_kb' => 0,
			'front_js_kb'   => 0,
			'front_css_kb'  => 0,
			'db_queries'    => 0,
			'external_http' => 0,
		);
	}

	/**
	 * Settings schema. Return an array of field definitions to get an
	 * auto-rendered settings form and auto-sanitisation for free. Return an
	 * empty array if the module has no settings.
	 *
	 * Field shape:
	 *   'key'     => unique field key
	 *   'label'   => string
	 *   'type'    => text|number|toggle|select|radio|textarea|url|color
	 *   'default' => mixed
	 *   'help'    => string (optional)
	 *   'options' => array (for select/radio)
	 *   'min'/'max'/'step' => for number
	 *
	 * @return array[]
	 */
	public function settings_schema() {
		return array();
	}

	/*
	 * -----------------------------------------------------------------------
	 * Settings storage (provided — modules don't reimplement this).
	 * -----------------------------------------------------------------------
	 */

	/**
	 * The wp_options key for this module's settings.
	 *
	 * @return string
	 */
	final public function settings_option_key() {
		return 'dicestack_settings_' . $this->id();
	}

	/**
	 * Get all settings for this module, merged with schema defaults.
	 *
	 * @return array
	 */
	final public function get_settings() {
		if ( null !== $this->settings_cache ) {
			return $this->settings_cache;
		}

		$stored   = get_option( $this->settings_option_key(), array() );
		$stored   = is_array( $stored ) ? $stored : array();
		$defaults = array();

		foreach ( $this->settings_schema() as $field ) {
			if ( isset( $field['key'] ) ) {
				$defaults[ $field['key'] ] = isset( $field['default'] ) ? $field['default'] : '';
			}
		}

		$this->settings_cache = wp_parse_args( $stored, $defaults );
		return $this->settings_cache;
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $fallback Fallback if not set.
	 * @return mixed
	 */
	final public function get_setting( $key, $fallback = null ) {
		$settings = $this->get_settings();
		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $fallback;
	}

	/**
	 * Persist settings after sanitising against the schema.
	 *
	 * Overridable: a module that stores values the schema sanitiser can't handle
	 * (e.g. raw script markup gated by a capability) may override this. Such
	 * overrides MUST still perform their own capability check and sanitisation.
	 *
	 * @param array $input Raw input.
	 * @return array The saved (sanitised) settings.
	 */
	public function save_settings( array $input ) {
		$clean = array();

		foreach ( $this->settings_schema() as $field ) {
			$key  = isset( $field['key'] ) ? $field['key'] : null;
			$type = isset( $field['type'] ) ? $field['type'] : 'text';
			if ( null === $key ) {
				continue;
			}

			// Checkboxes/toggles: absence means "off" (unchecked boxes are not posted).
			if ( 'toggle' === $type || 'checkbox' === $type ) {
				$raw           = isset( $input[ $key ] ) ? (string) $input[ $key ] : '';
				$clean[ $key ] = ( '1' === $raw || 'true' === $raw || 'on' === $raw );
				continue;
			}

			$value = isset( $input[ $key ] ) ? $input[ $key ] : ( isset( $field['default'] ) ? $field['default'] : '' );

			switch ( $type ) {
				case 'number':
					$num = is_numeric( $value ) ? $value + 0 : 0;
					if ( isset( $field['min'] ) && $num < $field['min'] ) {
						$num = $field['min'] + 0;
					}
					if ( isset( $field['max'] ) && $num > $field['max'] ) {
						$num = $field['max'] + 0;
					}
					$clean[ $key ] = $num;
					break;
				case 'password':
					// Stored as-is (credentials); only trim outer whitespace/newlines.
					$clean[ $key ] = trim( (string) wp_unslash( $value ) );
					break;
				case 'url':
					$clean[ $key ] = esc_url_raw( (string) $value );
					break;
				case 'color':
					$clean[ $key ] = sanitize_hex_color( (string) $value );
					break;
				case 'textarea':
					$clean[ $key ] = sanitize_textarea_field( (string) $value );
					break;
				case 'select':
				case 'radio':
					$allowed       = isset( $field['options'] ) ? array_keys( $field['options'] ) : array();
					$clean[ $key ] = in_array( $value, $allowed, true ) ? $value : ( isset( $field['default'] ) ? $field['default'] : '' );
					break;
				default:
					$clean[ $key ] = sanitize_text_field( (string) $value );
					break;
			}
		}

		update_option( $this->settings_option_key(), $clean );
		$this->settings_cache = null;
		return $clean;
	}

	/**
	 * Whether this module is currently active.
	 *
	 * @return bool
	 */
	final public function is_active() {
		return \DiceStack\Core::instance()->is_module_active( $this->id() );
	}
}
