<?php
/**
 * WP-CLI commands for DiceStack.
 *
 * @package DiceStack
 */

namespace DiceStack;

defined( 'ABSPATH' ) || exit;

/**
 * Manage DiceStack modules from the command line:
 *
 *   wp dicestack list [--enabled] [--disabled]
 *   wp dicestack status
 *   wp dicestack enable <module-id>...
 *   wp dicestack disable <module-id>...
 *   wp dicestack clear-cache
 */
final class CLI {

	/**
	 * List modules.
	 *
	 * ## OPTIONS
	 *
	 * [--enabled]
	 * : Only show enabled modules.
	 *
	 * [--disabled]
	 * : Only show disabled modules.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 * @return void
	 */
	public function list( $args, $assoc_args ) {
		$core    = Core::instance();
		$reg     = $core->registry();
		$active  = $core->get_active_modules();
		$rows    = array();

		foreach ( $reg->catalog() as $id => $class ) {
			$module = $reg->get_instance( $id );
			if ( ! $module ) {
				continue;
			}
			$on = in_array( $id, $active, true );
			if ( isset( $assoc_args['enabled'] ) && ! $on ) {
				continue;
			}
			if ( isset( $assoc_args['disabled'] ) && $on ) {
				continue;
			}
			$rows[] = array(
				'id'       => $id,
				'name'     => $module->name(),
				'category' => $module->category(),
				'status'   => $on ? 'enabled' : 'disabled',
			);
		}

		\WP_CLI\Utils\format_items( 'table', $rows, array( 'id', 'name', 'category', 'status' ) );
	}

	/**
	 * Show a summary of DiceStack status.
	 *
	 * @return void
	 */
	public function status() {
		$core   = Core::instance();
		$active = $core->get_active_modules();
		\WP_CLI::line( 'DiceStack ' . DICESTACK_VERSION );
		\WP_CLI::line( 'Total tools:   ' . $core->registry()->count() );
		\WP_CLI::line( 'Enabled tools: ' . count( $active ) );
		if ( ! empty( $active ) ) {
			\WP_CLI::line( 'Enabled: ' . implode( ', ', $active ) );
		}
	}

	/**
	 * Enable one or more modules.
	 *
	 * ## OPTIONS
	 *
	 * <module-id>...
	 * : One or more module IDs.
	 *
	 * @param array $args Module IDs.
	 * @return void
	 */
	public function enable( $args ) {
		$core = Core::instance();
		foreach ( $args as $id ) {
			if ( $core->enable_module( $id ) ) {
				\WP_CLI::success( "Enabled: {$id}" );
			} else {
				\WP_CLI::warning( "Could not enable (unknown, unsupported, or already on): {$id}" );
			}
		}
	}

	/**
	 * Disable one or more modules.
	 *
	 * ## OPTIONS
	 *
	 * <module-id>...
	 * : One or more module IDs.
	 *
	 * @param array $args Module IDs.
	 * @return void
	 */
	public function disable( $args ) {
		$core = Core::instance();
		foreach ( $args as $id ) {
			if ( $core->disable_module( $id ) ) {
				\WP_CLI::success( "Disabled: {$id}" );
			} else {
				\WP_CLI::warning( "Could not disable (unknown or already off): {$id}" );
			}
		}
	}

	/**
	 * Clear DiceStack's page cache and minified asset cache.
	 *
	 * @return void
	 */
	public function clear_cache() {
		$uploads = wp_get_upload_dir();
		$removed = 0;
		foreach ( array( 'dicestack-cache', 'dicestack-min' ) as $dir ) {
			$path = trailingslashit( $uploads['basedir'] ) . $dir;
			$files = is_dir( $path ) ? glob( $path . '/*' ) : array();
			if ( is_array( $files ) ) {
				foreach ( $files as $file ) {
					if ( is_file( $file ) && 'index.html' !== basename( $file ) ) {
						// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
						@unlink( $file );
						$removed++;
					}
				}
			}
		}
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		\WP_CLI::success( "Cache cleared ({$removed} files removed)." );
	}
}
