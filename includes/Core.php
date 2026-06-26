<?php
/**
 * Core bootstrap.
 *
 * @package DiceStack
 */

namespace DiceStack;

defined( 'ABSPATH' ) || exit;

/**
 * The central controller. Loads active modules and wires up the admin.
 *
 * Performance contract: when a module is disabled, its class file is never
 * required and its hooks are never registered. The only cost on every request
 * is a single (object-cached) read of the active-modules option.
 */
final class Core {

	/**
	 * Option key holding the array of active module IDs.
	 */
	const ACTIVE_OPTION = 'dicestack_active_modules';

	/**
	 * Option key holding the "first run completed" flag.
	 */
	const SETUP_OPTION = 'dicestack_setup_complete';

	/**
	 * Option key holding modules auto-disabled after a fatal error.
	 */
	const FAILURES_OPTION = 'dicestack_module_failures';

	/**
	 * Singleton instance.
	 *
	 * @var Core|null
	 */
	private static $instance = null;

	/**
	 * The module registry.
	 *
	 * @var Module_Registry|null
	 */
	private $registry = null;

	/**
	 * Instantiated active module objects, keyed by module ID.
	 *
	 * @var array<string, Modules\Abstract_Module>
	 */
	private $loaded = array();

	/**
	 * Get the singleton.
	 *
	 * @return Core
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor (singleton).
	 */
	private function __construct() {
		$this->registry = new Module_Registry();
	}

	/**
	 * Get the registry.
	 *
	 * @return Module_Registry
	 */
	public function registry() {
		return $this->registry;
	}

	/**
	 * Get loaded module objects.
	 *
	 * @return array<string, Modules\Abstract_Module>
	 */
	public function loaded_modules() {
		return $this->loaded;
	}

	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public function boot() {
		// Translations are loaded automatically by WordPress.org for hosted plugins
		// (since WP 4.6), so no load_plugin_textdomain() call is needed.

		// Admin is always available (the dashboard itself must load to toggle modules).
		if ( is_admin() ) {
			( new Admin\Admin() )->init();
			( new Site_Health() )->init();
		}

		// Developer command-line interface.
		if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( '\WP_CLI' ) ) {
			\WP_CLI::add_command( 'dicestack', new CLI() );
		}

		// Recovery hatch: define( 'DICESTACK_SAFE_MODE', true ) in wp-config.php to load
		// the dashboard but NONE of the modules, so a locked-out admin can recover.
		if ( defined( 'DICESTACK_SAFE_MODE' ) && DICESTACK_SAFE_MODE ) {
			do_action( 'dicestack_loaded', $this );
			return;
		}

		$this->load_active_modules();

		/**
		 * Fires after DiceStack has loaded all active modules.
		 *
		 * @param Core $core The core instance.
		 */
		do_action( 'dicestack_loaded', $this );
	}

	/**
	 * Instantiate every active, available module.
	 *
	 * @return void
	 */
	private function load_active_modules() {
		$active = $this->get_active_modules();

		if ( empty( $active ) ) {
			return;
		}

		foreach ( $active as $module_id ) {
			// Lazily instantiates and caches; only active modules are loaded.
			$module = $this->registry->get_instance( $module_id );
			if ( ! $module ) {
				continue;
			}

			// Respect dependencies (e.g. WooCommerce modules need WooCommerce).
			if ( ! $this->registry->dependencies_met( $module_id ) ) {
				continue;
			}

			// Isolate each module: if one fatals on load, disable it and keep the
			// site (and every other module) running instead of white-screening.
			try {
				$module->init();
				$this->loaded[ $module_id ] = $module;
			} catch ( \Throwable $e ) {
				$this->disable_module( $module_id );
				$this->record_module_failure( $module_id, $e->getMessage() );
			}
		}
	}

	/**
	 * Record that a module was auto-disabled after erroring, so the dashboard can
	 * tell the user what happened.
	 *
	 * @param string $module_id Module id.
	 * @param string $message   Error message.
	 * @return void
	 */
	private function record_module_failure( $module_id, $message ) {
		$fails = get_option( self::FAILURES_OPTION, array() );
		$fails = is_array( $fails ) ? $fails : array();
		$fails[ $module_id ] = array(
			'time' => time(),
			'msg'  => (string) $message,
		);
		update_option( self::FAILURES_OPTION, $fails, false );
	}

	/**
	 * Get the list of active module IDs.
	 *
	 * @return string[]
	 */
	public function get_active_modules() {
		$active = get_option( self::ACTIVE_OPTION, array() );
		return is_array( $active ) ? array_values( array_unique( $active ) ) : array();
	}

	/**
	 * Is a given module active?
	 *
	 * @param string $module_id Module identifier.
	 * @return bool
	 */
	public function is_module_active( $module_id ) {
		return in_array( $module_id, $this->get_active_modules(), true );
	}

	/**
	 * Enable a module.
	 *
	 * @param string $module_id Module identifier.
	 * @return bool True on change.
	 */
	public function enable_module( $module_id ) {
		$catalog = $this->registry->catalog();
		if ( ! isset( $catalog[ $module_id ] ) ) {
			return false;
		}
		// Never enable a module the server can't actually run.
		if ( ! $this->registry->requirements_met( $module_id ) || ! $this->registry->dependencies_met( $module_id ) ) {
			return false;
		}
		$active = $this->get_active_modules();
		if ( in_array( $module_id, $active, true ) ) {
			return false;
		}
		$active[] = $module_id;
		update_option( self::ACTIVE_OPTION, array_values( $active ) );

		/**
		 * Fires when a module is enabled. Modules use this to run one-time setup
		 * (e.g. add rewrite rules, create tables, show a consent notice).
		 *
		 * @param string $module_id Module identifier.
		 */
		do_action( 'dicestack_module_enabled', $module_id );
		do_action( "dicestack_module_enabled_{$module_id}" );
		return true;
	}

	/**
	 * Disable a module.
	 *
	 * @param string $module_id Module identifier.
	 * @return bool True on change.
	 */
	public function disable_module( $module_id ) {
		$active = $this->get_active_modules();
		$index  = array_search( $module_id, $active, true );
		if ( false === $index ) {
			return false;
		}
		unset( $active[ $index ] );
		update_option( self::ACTIVE_OPTION, array_values( $active ) );

		/**
		 * Fires when a module is disabled. Modules use this to tear down
		 * (e.g. flush rewrite rules, remove scheduled events).
		 *
		 * @param string $module_id Module identifier.
		 */
		do_action( 'dicestack_module_disabled', $module_id );
		do_action( "dicestack_module_disabled_{$module_id}" );
		return true;
	}

	/**
	 * Activation hook.
	 *
	 * @return void
	 */
	public static function on_activate() {
		// On first install, switch on a safe, high-value, zero-config default set
		// so the plugin is useful out of the box. Existing installs are untouched.
		if ( false === get_option( self::ACTIVE_OPTION, false ) ) {
			$registry = self::instance()->registry();
			$covered  = \DiceStack\Environment::detected_plugins();
			$safe     = array();
			foreach ( self::recommended_defaults() as $id ) {
				$feature = \DiceStack\Environment::module_feature( $id );
				if ( '' !== $feature && isset( $covered[ $feature ] ) ) {
					continue; // Another active plugin already handles this — don't clash.
				}
				if ( ! $registry->requirements_met( $id ) || ! $registry->dependencies_met( $id ) ) {
					continue; // Server can't run it.
				}
				$safe[] = $id;
			}
			add_option( self::ACTIVE_OPTION, $safe );
		}
		if ( false === get_option( self::SETUP_OPTION, false ) ) {
			add_option( self::SETUP_OPTION, false );
		}
		// Let modules register rewrite rules before we flush.
		flush_rewrite_rules();
	}

	/**
	 * Modules enabled by default on a fresh install. Deliberately limited to
	 * safe, no-configuration, non-destructive, non-WooCommerce modules.
	 *
	 * @return string[]
	 */
	public static function recommended_defaults() {
		return array(
			'security_hardening',
			'login_protection',
			'disable_xmlrpc',
			'login_errors',
			'lazy_loading',
			'disable_emojis',
			'clean_head',
			'meta_tags',
			'schema_jsonld',
			'spam_shield',
			'limit_revisions',
			'disable_self_pingbacks',
		);
	}

	/**
	 * Deactivation hook.
	 *
	 * @return void
	 */
	public static function on_deactivate() {
		flush_rewrite_rules();
	}
}
