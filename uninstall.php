<?php
/**
 * DiceStack uninstall routine.
 *
 * Removes all DiceStack options so an uninstall leaves no orphaned data
 * (WordPress.org best practice). Module-created tables/cron are cleaned up by
 * each module on disable; this is the final sweep of the options table.
 *
 * @package DiceStack
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Core options.
delete_option( 'dicestack_active_modules' );
delete_option( 'dicestack_setup_complete' );
delete_option( 'dicestack_db_last_cleanup' );
delete_option( 'dicestack_activity_log' );
delete_option( 'dicestack_error_log' );
delete_option( 'dicestack_shortcodes' );
delete_option( 'dicestack_reviews' );
delete_option( 'dicestack_change_log' );
delete_option( 'dicestack_module_failures' );
delete_option( 'dicestack_htaccess_backup' );
delete_option( 'dicestack_styler' );
delete_option( 'dicestack_404_log' );
delete_option( 'dicestack_404_redirects' );
delete_option( 'dicestack_last_backup' );
delete_option( 'dicestack_cloud_last' );

// Scheduled events created by modules.
wp_clear_scheduled_hook( 'dicestack_db_cleanup' );
wp_clear_scheduled_hook( 'dicestack_update_check' );
wp_clear_scheduled_hook( 'dicestack_scheduled_backup' );
wp_clear_scheduled_hook( 'dicestack_monthly_report' );
wp_clear_scheduled_hook( 'dicestack_cache_preload' );

// Remove our object-cache drop-in (only if it carries our signature).
$dicestack_dropin = WP_CONTENT_DIR . '/object-cache.php';
if ( file_exists( $dicestack_dropin ) ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$dicestack_head = (string) file_get_contents( $dicestack_dropin );
	if ( false !== strpos( $dicestack_head, 'DICESTACK_OBJECT_CACHE_DROPIN' ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		@unlink( $dicestack_dropin );
	}
}

// Cache directories (page cache + minified CSS/JS).
$dicestack_uploads = wp_get_upload_dir();
foreach ( array( 'dicestack-cache', 'dicestack-min' ) as $dicestack_cache_dir ) {
	$dicestack_path = trailingslashit( $dicestack_uploads['basedir'] ) . $dicestack_cache_dir;
	if ( is_dir( $dicestack_path ) ) {
		$dicestack_files = glob( $dicestack_path . '/*' );
		if ( is_array( $dicestack_files ) ) {
			foreach ( $dicestack_files as $dicestack_file ) {
				if ( is_file( $dicestack_file ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPress.WP.AlternativeFunctions.file_system_operations_unlink -- removing our own cache file on uninstall.
					@unlink( $dicestack_file );
				}
			}
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rmdir_rmdir, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- removing our own empty cache dir on uninstall.
		@rmdir( $dicestack_path );
	}
}

// All per-module settings (dicestack_settings_*).
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE 'dicestack\\_settings\\_%'"
);

// Transients created by modules (e.g. login lockouts).
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%\\_transient\\_dicestack\\_%' OR option_name LIKE '%\\_transient\\_timeout\\_dicestack\\_%'"
);

// Post meta written by SEO module.
delete_post_meta_by_key( '_dicestack_meta_title' );
delete_post_meta_by_key( '_dicestack_meta_description' );
delete_post_meta_by_key( '_dicestack_views' );

// Stored contact-form entries and newsletter subscribers (custom post types).
foreach ( array( 'dicestack_entry', 'dicestack_subscriber', 'dicestack_form_log' ) as $dicestack_cpt ) {
	$dicestack_posts = get_posts(
		array(
			'post_type'   => $dicestack_cpt,
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
		)
	);
	foreach ( $dicestack_posts as $dicestack_post_id ) {
		wp_delete_post( $dicestack_post_id, true );
	}
}
